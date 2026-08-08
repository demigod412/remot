<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Work;
use App\Models\WorkCategory;
use App\Models\WorkSubcategory;
use App\Models\WorkSubmission;
use App\Services\BatchApplicationApprovalService;
use App\Services\TaskApplicationService;
use Illuminate\Support\Facades\DB;

/**
 * Random-rate batch approval.
 *
 * The properties worth protecting are the ones a worker would dispute:
 * that the rate holds steady across a day, that rejection keeps the fee, that a
 * decision an admin already made by hand is never overwritten, and that the draw
 * cannot happen twice for the same worker on the same day.
 */
class BatchApplicationApprovalTest extends FeatureTestCase
{
    private BatchApplicationApprovalService $batch;
    private TaskApplicationService $applications;

    protected function setUp(): void
    {
        parent::setUp();
        $this->batch        = app(BatchApplicationApprovalService::class);
        $this->applications = app(TaskApplicationService::class);
    }

    private function makeCategory(int $min = 40, int $max = 70, bool $enabled = true, float $fee = 10): WorkCategory
    {
        return WorkCategory::create([
            'name'                    => 'Cat ' . bin2hex(random_bytes(3)),
            'status'                  => 1,
            'application_cost'        => $fee,
            'commission_percent'      => 10,
            'eligible_user_type'      => 0,
            'daily_application_limit' => 0,
            'min_approval_rate'       => $min,
            'max_approval_rate'       => $max,
            'batch_approval_enabled'  => $enabled,
        ]);
    }

    private function makeWork(WorkCategory $category): Work
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
            'title'            => 'Batch test task',
            'description'      => 'Do the thing.',
            'worker_slots'     => 20,
            'total_coins'      => 0,
            'coins_per_worker' => 0,
            'payout_usd'       => 10,
            'work_status'      => 1,
            'approval_status'  => 1,
        ]);
    }

    private function applyTimes(User $worker, WorkCategory $category, int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            $this->applications->apply($worker, $this->makeWork($category));
        }
    }

    public function test_a_disabled_category_is_left_alone(): void
    {
        $category = $this->makeCategory(enabled: false);
        $worker   = $this->makeUser([], 1000);
        $this->applyTimes($worker, $category, 4);

        $this->batch->run();

        $this->assertSame(4, WorkSubmission::where('application_status', WorkSubmission::APP_APPLIED)->count(),
            'Batch approval must be opt-in per category.');
    }

    public function test_the_rate_is_drawn_once_per_worker_per_category_per_day(): void
    {
        $category = $this->makeCategory(min: 10, max: 90);
        $worker   = $this->makeUser([], 1000);

        $first = $this->batch->rateFor($worker->id, $category);

        // Twenty further calls must all return the same figure. With a 10-90 range a
        // redraw would almost certainly differ, so this would fail loudly.
        for ($i = 0; $i < 20; $i++) {
            $this->assertSame($first, $this->batch->rateFor($worker->id, $category));
        }

        $this->assertSame(1, DB::table('application_approval_draws')->count());
    }

    public function test_the_rate_lands_inside_the_configured_band(): void
    {
        $category = $this->makeCategory(min: 40, max: 70);

        for ($i = 0; $i < 25; $i++) {
            $rate = $this->batch->rateFor($this->makeUser([], 0)->id, $category);
            $this->assertGreaterThanOrEqual(40, $rate);
            $this->assertLessThanOrEqual(70, $rate);
        }
    }

    public function test_different_categories_draw_separately(): void
    {
        $a      = $this->makeCategory();
        $b      = $this->makeCategory();
        $worker = $this->makeUser([], 1000);

        $this->batch->rateFor($worker->id, $a);
        $this->batch->rateFor($worker->id, $b);

        $this->assertSame(2, DB::table('application_approval_draws')->count(),
            'One draw per category, not one per worker.');
    }

    public function test_every_application_is_decided_and_none_left_pending(): void
    {
        $category = $this->makeCategory();
        $worker   = $this->makeUser([], 1000);
        $this->applyTimes($worker, $category, 6);

        $this->batch->run();

        $this->assertSame(0, WorkSubmission::where('application_status', WorkSubmission::APP_APPLIED)->count());
        $this->assertSame(6, WorkSubmission::whereIn('application_status', [
            WorkSubmission::APP_APPROVED, WorkSubmission::APP_REJECTED,
        ])->count());
    }

    public function test_the_number_approved_matches_the_drawn_rate(): void
    {
        $category = $this->makeCategory(min: 50, max: 50);   // fixed, so the maths is checkable
        $worker   = $this->makeUser([], 1000);
        $this->applyTimes($worker, $category, 6);

        $this->batch->run();

        $this->assertSame(3, WorkSubmission::where('application_status', WorkSubmission::APP_APPROVED)->count(),
            '50% of 6 is 3.');
        $this->assertSame(3, WorkSubmission::where('application_status', WorkSubmission::APP_REJECTED)->count());
    }

    /**
     * The decision the platform owner made explicitly. If this ever fails, batch
     * rejections are being routed through TaskReviewService, which refunds.
     */
    public function test_a_batch_rejection_does_not_refund_the_fee(): void
    {
        $category = $this->makeCategory(min: 0, max: 0, fee: 25);   // reject everything
        $worker   = $this->makeUser([], 1000);
        $this->applyTimes($worker, $category, 4);

        $balanceAfterFees = (float) $worker->fresh()->coin_balance;
        $this->assertSame(900.0, $balanceAfterFees, '4 applications at 25 coins.');

        $this->batch->run();

        $this->assertSame(4, WorkSubmission::where('application_status', WorkSubmission::APP_REJECTED)->count());
        $this->assertSame($balanceAfterFees, (float) $worker->fresh()->coin_balance,
            'Lottery rejection keeps the fee.');
        $this->assertSame(0, \App\Models\LedgerEntry::where('category', 'task_apply_refund')->count());
    }

    public function test_approved_applications_get_a_unique_annotate_code(): void
    {
        $category = $this->makeCategory(min: 100, max: 100);   // approve everything
        $worker   = $this->makeUser([], 1000);
        $this->applyTimes($worker, $category, 5);

        $this->batch->run();

        $codes = WorkSubmission::whereNotNull('annotate_code')->pluck('annotate_code');

        $this->assertCount(5, $codes);
        $this->assertCount(5, $codes->unique(), 'Codes must be unique.');
        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^AN-[23456789ABCDEFGHJKMNPQRSTUVWXYZ]{8}$/', $code,
                'No 0/O or 1/I/L: these codes get read aloud and retyped.');
        }
    }

    public function test_an_admin_decision_is_never_overwritten(): void
    {
        $category = $this->makeCategory(min: 100, max: 100);
        $worker   = $this->makeUser([], 1000);
        $this->applyTimes($worker, $category, 3);

        // Admin refuses one by hand before the batch runs.
        $byHand = WorkSubmission::first();
        $byHand->update(['application_status' => WorkSubmission::APP_REJECTED]);

        $this->batch->run();

        $this->assertSame(WorkSubmission::APP_REJECTED, $byHand->fresh()->application_status,
            'The scheduler must not reverse a human decision.');
        $this->assertFalse((bool) $byHand->fresh()->approved_by_batch);
    }

    public function test_each_worker_is_judged_on_their_own_group(): void
    {
        $category = $this->makeCategory(min: 100, max: 100);
        $a = $this->makeUser([], 1000);
        $b = $this->makeUser([], 1000);

        $this->applyTimes($a, $category, 2);
        $this->applyTimes($b, $category, 3);

        $this->batch->run();

        $this->assertSame(2, WorkSubmission::where('worker_id', $a->id)
            ->where('application_status', WorkSubmission::APP_APPROVED)->count());
        $this->assertSame(3, WorkSubmission::where('worker_id', $b->id)
            ->where('application_status', WorkSubmission::APP_APPROVED)->count());
    }

    public function test_running_twice_does_nothing_the_second_time(): void
    {
        $category = $this->makeCategory(min: 100, max: 100);
        $worker   = $this->makeUser([], 1000);
        $this->applyTimes($worker, $category, 4);

        $this->batch->run();
        $second = $this->batch->run();

        $this->assertSame(0, $second['considered'], 'Nothing pending is left to consider.');
        $this->assertSame(4, WorkSubmission::where('application_status', WorkSubmission::APP_APPROVED)->count());
    }
}
