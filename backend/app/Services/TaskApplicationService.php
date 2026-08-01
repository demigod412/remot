<?php

namespace App\Services;

use App\Models\User;
use App\Models\Work;
use App\Models\WorkSubmission;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Everything that happens when a worker applies to a task.
 *
 * All of it lives in one transaction on purpose. The four things that must
 * happen or not happen together are:
 *
 *   1. the uniqueness check (one application per worker per task)
 *   2. the slot cap check (counting ALL live applications, not just approved)
 *   3. the coin deduction (the category's application_cost)
 *   4. the WorkSubmission row
 *
 * If 3 succeeds and 4 fails, the worker has paid for nothing. If 2 is checked
 * outside the transaction, two concurrent applicants both pass it and the task
 * oversells its slots.
 */
class TaskApplicationService
{
    /**
     * @throws ApplicationException with a human-readable reason
     */
    public function apply(User $user, Work $work): WorkSubmission
    {
        $this->assertEligible($user, $work);

        $reference = generateReference();

        try {
            return DB::transaction(function () use ($user, $work, $reference) {
                // Lock the task row. Two applicants racing for the last slot will
                // queue here instead of both reading slots_remaining = 1.
                $lockedWork = Work::whereKey($work->id)->lockForUpdate()->firstOrFail();

                $cost = $lockedWork->application_cost;

                // Re-check everything INSIDE the lock. The pre-flight checks above
                // are for fast, friendly errors; these are the authoritative ones.
                $this->assertNotAlreadyApplied($user, $lockedWork);
                $this->assertSlotAvailable($lockedWork);

                if ($cost > 0) {
                    // Throws RuntimeException on insufficient balance, which rolls
                    // the whole transaction back.
                    CoinService::deduct(
                        $user,
                        $cost,
                        $reference,
                        'task_apply',
                        'Applied to task: ' . $lockedWork->title
                    );
                }

                $submission = WorkSubmission::create([
                    'work_id'            => $lockedWork->id,
                    'work_poster_id'     => $lockedWork->poster_id,
                    'work_poster_type'   => $lockedWork->poster_type,
                    'worker_id'          => $user->id,
                    'worker_type'        => 2,
                    'application_status' => WorkSubmission::APP_APPLIED,
                    'delivery_status'    => WorkSubmission::DEL_NOT_STARTED,
                    // No worker deadline yet. The clock starts when admin approves
                    // the application and hands over the task files.
                    'deadline_at'        => null,
                    // Record what was actually charged and under which reference, so
                    // a refund later reverses the exact debit even if the category
                    // price has changed in the meantime.
                    'fee_paid'           => $cost,
                    'fee_reference'      => $cost > 0 ? $reference : null,
                ]);

                return $submission;
            });
        } catch (QueryException $e) {
            // The unique index on (work_id, worker_id) is the last line of defence
            // if two requests slip past the application check simultaneously.
            if ($this->isDuplicateKey($e)) {
                throw new ApplicationException('You have already applied to this task.');
            }
            throw $e;
        } catch (\RuntimeException $e) {
            // CoinService::deduct throws this for insufficient balance.
            throw new ApplicationException($e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Guards
    // -------------------------------------------------------------------------

    protected function assertEligible(User $user, Work $work): void
    {
        if ($work->work_status !== 1 || $work->approval_status !== 1) {
            throw new ApplicationException('This task is not open for applications.');
        }

        if ($work->expires_at && $work->expires_at->isPast()) {
            throw new ApplicationException('This task has closed.');
        }

        if ($user->status !== 1) {
            throw new ApplicationException('Your account is not active.');
        }

        if ($user->must_change_password) {
            throw new ApplicationException('Please change your password before applying to tasks.');
        }

        $category = $work->category;
        if ($category && ! $category->allowsUser($user)) {
            throw new ApplicationException(
                'This task category is open to ' . strtolower($category->eligible_user_type_label) . ' only.'
            );
        }

        if ($work->requires_kyc && $user->kyc_status !== 1) {
            throw new ApplicationException(
                'This task requires identity verification. Please complete KYC before applying.'
            );
        }

        $cost = $work->application_cost;
        if ($cost > 0 && ! CoinService::hasBalance($user, $cost)) {
            throw new ApplicationException(
                'You need ' . formatCoins($cost) . ' to apply to this task. Please top up.'
            );
        }

        // Reliability gate. Checked before the fee is taken, so a blocked worker is
        // never charged for an application that would have been refused anyway.
        $reliability = app(WorkerReliabilityService::class);
        if ($reliability->isBlocked($user)) {
            throw new ApplicationException($reliability->blockReason($user));
        }

        $this->assertNotAlreadyApplied($user, $work);
        $this->assertSlotAvailable($work);
    }

    protected function assertNotAlreadyApplied(User $user, Work $work): void
    {
        $exists = WorkSubmission::where('work_id', $work->id)
            ->where('worker_id', $user->id)
            ->where('worker_type', 2)
            ->exists();

        if ($exists) {
            throw new ApplicationException('You have already applied to this task.');
        }
    }

    protected function assertSlotAvailable(Work $work): void
    {
        $occupied = WorkSubmission::where('work_id', $work->id)
            ->occupyingSlot()
            ->count();

        if ($occupied >= $work->worker_slots) {
            throw new ApplicationException('All slots for this task are taken.');
        }
    }

    protected function isDuplicateKey(QueryException $e): bool
    {
        // 23000 / 23505 cover MySQL and Postgres integrity constraint violations.
        return in_array((string) $e->getCode(), ['23000', '23505'], true);
    }
}
