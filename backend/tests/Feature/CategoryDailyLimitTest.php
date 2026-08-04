<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Work;
use App\Models\WorkCategory;
use App\Models\WorkSubcategory;
use App\Models\WorkSubmission;
use App\Services\ApplicationException;
use App\Services\TaskApplicationService;

/**
 * Per-category daily application quota.
 *
 * work_categories.daily_application_limit caps how many tasks in one category a
 * worker may apply to per calendar day. 0 means unlimited, so every category that
 * existed before this feature keeps working untouched — that is the property the
 * first test protects, and it matters more than the limit itself.
 */
class CategoryDailyLimitTest extends FeatureTestCase
{
    private TaskApplicationService $applications;

    protected function setUp(): void
    {
        parent::setUp();
        $this->applications = app(TaskApplicationService::class);
    }

    private function makeCategory(int $dailyLimit = 0, float $fee = 0): WorkCategory
    {
        return WorkCategory::create([
            'name'                    => 'Cat ' . bin2hex(random_bytes(3)),
            'status'                   => 1,
            'application_cost'         => $fee,
            'commission_percent'       => 10,
            'eligible_user_type'       => 0,
            'daily_application_limit'  => $dailyLimit,
        ]);
    }

    private function makeWork(WorkCategory $category, int $slots = 5): Work
    {
        $sub = WorkSubcategory::create([
            'category_id' => $category->id,
            'name'        => 'Sub ' . bin2hex(random_bytes(3)),
            'status'      => 1,
        ]);

        return Work::create([
            'poster_id'        => 1,
            'poster_type'      => 1,
            'category_id'      => $category->id,
            'subcategory_id'   => $sub->id,
            'slug'             => 'task-' . bin2hex(random_bytes(4)),
            'title'            => 'Limit test task',
            'description'      => 'Do the thing.',
            'worker_slots'     => $slots,
            'total_coins'      => 0,
            'coins_per_worker' => 0,
            'payout_usd'       => 10,
            'work_status'      => 1,
            'approval_status'  => 1,
        ]);
    }

    private function makeWorker(float $coins = 1000): User
    {
        return $this->makeUser(['kyc_status' => 1, 'must_change_password' => false], $coins);
    }

    public function test_zero_means_unlimited(): void
    {
        $category = $this->makeCategory(dailyLimit: 0);
        $worker   = $this->makeWorker();

        for ($i = 0; $i < 6; $i++) {
            $this->applications->apply($worker, $this->makeWork($category));
        }

        $this->assertSame(6, WorkSubmission::where('worker_id', $worker->id)->count());
    }

    public function test_the_limit_blocks_the_next_application(): void
    {
        $category = $this->makeCategory(dailyLimit: 3);
        $worker   = $this->makeWorker();

        for ($i = 0; $i < 3; $i++) {
            $this->applications->apply($worker, $this->makeWork($category));
        }

        $this->expectException(ApplicationException::class);
        $this->applications->apply($worker, $this->makeWork($category));
    }

    public function test_the_message_names_the_limit_and_the_category(): void
    {
        $category = $this->makeCategory(dailyLimit: 2);
        $worker   = $this->makeWorker();

        $this->applications->apply($worker, $this->makeWork($category));
        $this->applications->apply($worker, $this->makeWork($category));

        try {
            $this->applications->apply($worker, $this->makeWork($category));
            $this->fail('Expected the daily limit to block a third application.');
        } catch (ApplicationException $e) {
            $this->assertStringContainsString('2 tasks', $e->getMessage());
            $this->assertStringContainsString($category->name, $e->getMessage());
        }
    }

    /**
     * The quota is per category, so hitting it in one must not close another.
     */
    public function test_the_limit_is_scoped_to_one_category(): void
    {
        $starter = $this->makeCategory(dailyLimit: 1);
        $other   = $this->makeCategory(dailyLimit: 1);
        $worker  = $this->makeWorker();

        $this->applications->apply($worker, $this->makeWork($starter));
        $this->applications->apply($worker, $this->makeWork($other));

        $this->assertSame(2, WorkSubmission::where('worker_id', $worker->id)->count());
    }

    public function test_the_limit_is_per_worker(): void
    {
        $category = $this->makeCategory(dailyLimit: 1);
        $work     = $this->makeWork($category, slots: 5);

        $this->applications->apply($this->makeWorker(), $work);
        $this->applications->apply($this->makeWorker(), $work);

        $this->assertSame(2, WorkSubmission::where('work_id', $work->id)->count());
    }

    /**
     * Yesterday's applications must not eat into today's quota.
     */
    public function test_yesterdays_applications_do_not_count(): void
    {
        $category = $this->makeCategory(dailyLimit: 2);
        $worker   = $this->makeWorker();

        $old = $this->applications->apply($worker, $this->makeWork($category));
        $old->forceFill(['created_at' => now()->subDay()])->save();

        // Quota should be clear again, so two more must both succeed.
        $this->applications->apply($worker, $this->makeWork($category));
        $this->applications->apply($worker, $this->makeWork($category));

        $this->assertSame(3, WorkSubmission::where('worker_id', $worker->id)->count());
    }

    /**
     * An admin-rejected application is refunded, so it would be unfair to also spend
     * a day's quota on it.
     */
    public function test_a_rejected_application_frees_quota(): void
    {
        $category = $this->makeCategory(dailyLimit: 1);
        $worker   = $this->makeWorker();

        $first = $this->applications->apply($worker, $this->makeWork($category));
        $first->forceFill(['application_status' => WorkSubmission::APP_REJECTED])->save();

        $this->applications->apply($worker, $this->makeWork($category));

        $this->assertSame(2, WorkSubmission::where('worker_id', $worker->id)->count());
    }

    /**
     * The fee must not be taken when the quota refuses the application. The check
     * runs before any coin movement, so the balance is untouched.
     */
    public function test_a_blocked_application_is_not_charged(): void
    {
        $category = $this->makeCategory(dailyLimit: 1, fee: 25);
        $worker   = $this->makeWorker(coins: 1000);

        $this->applications->apply($worker, $this->makeWork($category));
        $balanceAfterFirst = (float) $worker->fresh()->coin_balance;

        try {
            $this->applications->apply($worker, $this->makeWork($category));
        } catch (ApplicationException) {
            // expected
        }

        $this->assertSame($balanceAfterFirst, (float) $worker->fresh()->coin_balance);
    }
}
