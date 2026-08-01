<?php

namespace Tests\Feature;

use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkCategory;
use App\Models\WorkSubcategory;
use App\Models\WorkSubmission;
use App\Services\ApplicationException;
use App\Services\TaskApplicationService;
use App\Services\TaskReviewService;

/**
 * Phase 5 money movement. Every test here exists because the failure it guards
 * against costs real coins.
 */
class TaskLifecycleTest extends FeatureTestCase
{
    protected TaskApplicationService $applications;
    protected TaskReviewService $reviews;

    protected function setUp(): void
    {
        parent::setUp();
        $this->applications = new TaskApplicationService();
        $this->reviews      = new TaskReviewService();
    }

    // -------------------------------------------------------------------------
    // Fixtures
    // -------------------------------------------------------------------------

    protected function makeCategory(float $fee = 10, float $commission = 20, int $eligible = 0): WorkCategory
    {
        return WorkCategory::create([
            'name'               => 'Cat ' . bin2hex(random_bytes(3)),
            'status'             => 1,
            'application_cost'   => $fee,
            'commission_percent' => $commission,
            'eligible_user_type' => $eligible,
        ]);
    }

    protected function makeWork(WorkCategory $category, int $slots = 2, float $payout = 100): Work
    {
        $sub = WorkSubcategory::create([
            'category_id' => $category->id,
            'name'        => 'Sub ' . bin2hex(random_bytes(3)),
            'status'      => 1,
        ]);

        return Work::create([
            'poster_id'        => 1,
            'poster_type'      => 1, // admin
            'category_id'      => $category->id,
            'subcategory_id'   => $sub->id,
            'slug'             => 'task-' . bin2hex(random_bytes(4)),
            'title'            => 'Test task',
            'description'      => 'Do the thing.',
            'worker_slots'     => $slots,
            'total_coins'      => $payout * $slots,
            'coins_per_worker' => $payout,
            'avg_minutes'      => 10,
            'work_status'      => 1,
            'approval_status'  => 1,
        ]);
    }

    // -------------------------------------------------------------------------
    // Applying
    // -------------------------------------------------------------------------

    public function test_applying_charges_the_category_fee_once(): void
    {
        $user = $this->makeUser([], 100);
        $work = $this->makeWork($this->makeCategory(fee: 15));

        $submission = $this->applications->apply($user, $work);

        $this->assertSame(85.0, (float) $user->fresh()->coin_balance);
        $this->assertSame(15.0, (float) $submission->fee_paid);
        $this->assertNotEmpty($submission->fee_reference);
        $this->assertDatabaseHas('ledger_entries', [
            'user_id'    => $user->id,
            'entry_type' => '-',
            'category'   => 'task_apply',
        ]);
    }

    public function test_cannot_apply_to_the_same_task_twice(): void
    {
        $user = $this->makeUser([], 100);
        $work = $this->makeWork($this->makeCategory(fee: 15));

        $this->applications->apply($user, $work);

        $this->expectException(ApplicationException::class);

        try {
            $this->applications->apply($user, $work);
        } finally {
            // Charged exactly once, not twice.
            $this->assertSame(85.0, (float) $user->fresh()->coin_balance);
            $this->assertSame(1, WorkSubmission::where('work_id', $work->id)->count());
        }
    }

    public function test_a_worker_can_apply_to_many_different_tasks(): void
    {
        $user = $this->makeUser([], 100);
        $cat  = $this->makeCategory(fee: 10);

        $this->applications->apply($user, $this->makeWork($cat));
        $this->applications->apply($user, $this->makeWork($cat));
        $this->applications->apply($user, $this->makeWork($cat));

        $this->assertSame(70.0, (float) $user->fresh()->coin_balance);
        $this->assertSame(3, WorkSubmission::where('worker_id', $user->id)->count());
    }

    public function test_insufficient_balance_blocks_application_and_creates_nothing(): void
    {
        $user = $this->makeUser([], 5);
        $work = $this->makeWork($this->makeCategory(fee: 20));

        $this->expectException(ApplicationException::class);

        try {
            $this->applications->apply($user, $work);
        } finally {
            $this->assertSame(5.0, (float) $user->fresh()->coin_balance);
            $this->assertSame(0, WorkSubmission::count());
        }
    }

    /** Slots must cap TOTAL applications, not just approved ones. */
    public function test_slots_cap_total_applications_not_just_approved(): void
    {
        $work = $this->makeWork($this->makeCategory(fee: 5), slots: 2);

        $this->applications->apply($this->makeUser([], 100), $work);
        $this->applications->apply($this->makeUser([], 100), $work);

        $third = $this->makeUser([], 100);

        $this->expectException(ApplicationException::class);

        try {
            $this->applications->apply($third, $work);
        } finally {
            $this->assertSame(100.0, (float) $third->fresh()->coin_balance);
        }
    }

    public function test_business_only_category_rejects_individual_applicant(): void
    {
        $user = $this->makeUser(['user_type' => User::TYPE_INDIVIDUAL], 100);
        $work = $this->makeWork($this->makeCategory(fee: 5, eligible: WorkCategory::ELIGIBLE_BUSINESS));

        $this->expectException(ApplicationException::class);

        try {
            $this->applications->apply($user, $work);
        } finally {
            $this->assertSame(100.0, (float) $user->fresh()->coin_balance);
        }
    }

    // -------------------------------------------------------------------------
    // Rejecting an application refunds
    // -------------------------------------------------------------------------

    public function test_rejecting_an_application_refunds_the_exact_fee(): void
    {
        $user = $this->makeUser([], 100);
        $work = $this->makeWork($this->makeCategory(fee: 25));

        $submission = $this->applications->apply($user, $work);
        $this->assertSame(75.0, (float) $user->fresh()->coin_balance);

        $this->reviews->rejectApplication($submission, 'Not a fit.');

        $this->assertSame(100.0, (float) $user->fresh()->coin_balance);
        $this->assertDatabaseHas('ledger_entries', [
            'user_id'  => $user->id,
            'category' => 'task_apply_refund',
        ]);
    }

    /** Refund must be idempotent, or a double click pays the fee back twice. */
    public function test_refund_cannot_be_issued_twice_for_one_application(): void
    {
        $user = $this->makeUser([], 100);
        $work = $this->makeWork($this->makeCategory(fee: 25));

        $submission = $this->applications->apply($user, $work);
        $this->reviews->rejectApplication($submission, 'Not a fit.');

        // Second attempt must not move coins again, whatever it throws.
        try {
            $this->reviews->rejectApplication($submission->fresh(), 'Again.');
        } catch (\Throwable) {
            // Expected.
        }

        $this->assertSame(100.0, (float) $user->fresh()->coin_balance);
        $this->assertSame(1, LedgerEntry::where('user_id', $user->id)
            ->where('category', 'task_apply_refund')->count());
    }

    /** A rejected application must release its slot for someone else. */
    public function test_rejected_application_frees_the_slot(): void
    {
        $work = $this->makeWork($this->makeCategory(fee: 5), slots: 1);

        $first  = $this->makeUser([], 100);
        $second = $this->makeUser([], 100);

        $submission = $this->applications->apply($first, $work);
        $this->reviews->rejectApplication($submission, 'No.');

        // Should not throw now that the slot is free.
        $new = $this->applications->apply($second, $work);

        $this->assertNotNull($new->id);
        $this->assertSame(95.0, (float) $second->fresh()->coin_balance);
    }

    // -------------------------------------------------------------------------
    // Approving delivered work pays out with commission
    // -------------------------------------------------------------------------

    public function test_approving_work_pays_net_and_records_commission(): void
    {
        $user = $this->makeUser([], 100);
        $cat  = $this->makeCategory(fee: 10, commission: 20);
        $work = $this->makeWork($cat, slots: 2, payout: 100);

        $submission = $this->applications->apply($user, $work);
        $this->reviews->approveApplication($submission, ['pkg.zip'], 'Instructions here, long enough.');

        $submission->update([
            'delivery_status' => WorkSubmission::DEL_SUBMITTED,
            'submitted_at'    => now(),
        ]);

        $this->reviews->approveSubmission($submission->fresh());

        // 100 balance - 10 fee + 80 net = 170
        $this->assertSame(170.0, (float) $user->fresh()->coin_balance);

        $this->assertDatabaseHas('ledger_entries', [
            'user_id'  => $user->id,
            'category' => 'work_earn',
            'coins'    => '80.00000000',
        ]);

        // Commission is booked against the platform (user_id 0), not a person.
        $this->assertDatabaseHas('ledger_entries', [
            'user_id'  => 0,
            'category' => 'task_commission',
            'coins'    => '20.00000000',
        ]);
    }

    public function test_commission_and_net_always_reconcile_to_gross(): void
    {
        foreach ([0, 7.5, 15, 33.33, 100] as $percent) {
            $cat   = $this->makeCategory(fee: 0, commission: $percent);
            $gross = 99.99;

            $commission = $cat->calculateCommission($gross);
            $net        = $cat->calculateNetPayout($gross);

            $this->assertSame(
                round($gross, 2),
                round($commission + $net, 2),
                "Split did not reconcile at {$percent}%"
            );
        }
    }

    public function test_approving_the_same_work_twice_does_not_pay_twice(): void
    {
        $user = $this->makeUser([], 100);
        $work = $this->makeWork($this->makeCategory(fee: 0, commission: 0), payout: 50);

        $submission = $this->applications->apply($user, $work);
        $this->reviews->approveApplication($submission, ['pkg.zip'], 'Instructions here, long enough.');
        $submission->update(['delivery_status' => WorkSubmission::DEL_SUBMITTED, 'submitted_at' => now()]);

        $this->reviews->approveSubmission($submission->fresh());

        try {
            $this->reviews->approveSubmission($submission->fresh());
        } catch (ApplicationException) {
            // Expected.
        }

        $this->assertSame(150.0, (float) $user->fresh()->coin_balance);
        $this->assertSame(1, LedgerEntry::where('user_id', $user->id)
            ->where('category', 'work_earn')->count());
    }

    // -------------------------------------------------------------------------
    // Rejections and expiry keep the fee
    // -------------------------------------------------------------------------

    public function test_rejecting_delivered_work_pays_nothing_and_keeps_the_fee(): void
    {
        $user = $this->makeUser([], 100);
        $work = $this->makeWork($this->makeCategory(fee: 20), payout: 100);

        $submission = $this->applications->apply($user, $work);
        $this->reviews->approveApplication($submission, ['pkg.zip'], 'Instructions here, long enough.');
        $submission->update(['delivery_status' => WorkSubmission::DEL_SUBMITTED, 'submitted_at' => now()]);

        $this->reviews->rejectSubmission($submission->fresh(), 'Quality too low.');

        // Fee spent, no payout, no refund.
        $this->assertSame(80.0, (float) $user->fresh()->coin_balance);
        $this->assertSame(0, LedgerEntry::where('user_id', $user->id)
            ->where('category', 'work_earn')->count());
        $this->assertSame(0, LedgerEntry::where('user_id', $user->id)
            ->where('category', 'task_apply_refund')->count());
    }

    public function test_abandoned_assignment_expires_frees_slot_and_keeps_fee(): void
    {
        $work = $this->makeWork($this->makeCategory(fee: 20), slots: 1, payout: 100);
        $user = $this->makeUser([], 100);

        $submission = $this->applications->apply($user, $work);
        $this->reviews->approveApplication($submission, ['pkg.zip'], 'Instructions here, long enough.');

        // Push the deadline into the past.
        $submission->fresh()->update(['deadline_at' => now()->subHour()]);

        $this->reviews->expireAbandoned($submission->fresh());

        $this->assertSame(80.0, (float) $user->fresh()->coin_balance);
        $this->assertSame(
            WorkSubmission::DEL_EXPIRED,
            $submission->fresh()->delivery_status
        );

        // Slot released, so someone else can take it.
        $other = $this->makeUser([], 100);
        $this->assertNotNull($this->applications->apply($other, $work->fresh())->id);
    }

    // -------------------------------------------------------------------------
    // Legacy mirror
    // -------------------------------------------------------------------------

    public function test_legacy_status_column_stays_in_sync(): void
    {
        $user = $this->makeUser([], 100);
        $work = $this->makeWork($this->makeCategory(fee: 0));

        $submission = $this->applications->apply($user, $work);
        $this->assertSame(WorkSubmission::LEGACY_APPLIED, $submission->fresh()->status);

        $this->reviews->approveApplication($submission, ['pkg.zip'], 'Instructions here, long enough.');
        $this->assertSame(WorkSubmission::LEGACY_UNDER_REVIEW, $submission->fresh()->status);

        $submission->fresh()->update(['delivery_status' => WorkSubmission::DEL_SUBMITTED, 'submitted_at' => now()]);
        $this->reviews->approveSubmission($submission->fresh());
        $this->assertSame(WorkSubmission::LEGACY_APPROVED, $submission->fresh()->status);

        // Legacy scopes must still find it.
        $this->assertTrue(WorkSubmission::approved()->where('id', $submission->id)->exists());
    }
}
