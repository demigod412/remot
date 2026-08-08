<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkSubmission;
use Illuminate\Support\Facades\DB;

/**
 * The four admin actions on a task application, plus the system auto-approval.
 *
 * Refund policy, decided deliberately:
 *
 *   application rejected   fee REFUNDED   worker never got to do anything
 *   work rejected          fee KEPT       worker consumed a slot and admin time
 *   worker missed deadline fee KEPT       same, and the slot is freed for others
 *   work approved          fee KEPT       worker is paid the payout, minus commission
 *
 * Every method that moves coins runs inside a transaction together with the
 * status change, so a failed payout can never leave a submission marked paid.
 */
class TaskReviewService
{
    // -------------------------------------------------------------------------
    // 1. Approve the application and hand over the task
    // -------------------------------------------------------------------------

    /**
     * @param  array  $taskFiles  Stored filenames of the task package
     */
    public function approveApplication(
        WorkSubmission $submission,
        array $taskFiles,
        // Nullable since the zip flow was removed: the task's own instructions live
        // in the console, and this field is now only for anything specific to one
        // worker. Typed as string it threw a TypeError the moment it was left blank.
        ?string $instructions = null
    ): WorkSubmission {
        if (! $submission->isAwaitingApplicationReview()) {
            throw new ApplicationException('This application has already been reviewed.');
        }

        return DB::transaction(function () use ($submission, $taskFiles, $instructions) {
            $work  = $submission->work;
            $hours = $work ? $work->review_window_hours : (int) config('jobstation.task_review_hours', 48);

            $submission->update([
                'application_status' => WorkSubmission::APP_APPROVED,
                'delivery_status'    => WorkSubmission::DEL_NOT_STARTED,
                // Without this, an admin-approved worker had no code and could not
                // open the console at all — only the batch job issued one. Both
                // approval paths now use the same generator.
                'annotate_code'      => app(AnnotateCodeGenerator::class)->generate(),
                'task_files'         => $taskFiles,
                'task_instructions'  => $instructions,
                'task_delivered_at'  => now(),
                // The worker's clock starts now, not at application time.
                'deadline_at'        => now()->addHours($hours),
                'is_read'            => 1,
            ]);

            ActivityLogger::log('task.application.approve', $submission, [
                'work_id'     => $submission->work_id,
                'worker_id'   => $submission->worker_id,
                'files'       => count($taskFiles),
                'deadline_at' => $submission->deadline_at?->toDateTimeString(),
            ]);

            $this->notifyWorker($submission, 'TASK_ASSIGNED', [
                'work_title' => $work->title ?? '',
                'deadline'   => $submission->deadline_at?->toDayDateTimeString() ?? '',
            ]);

            return $submission;
        });
    }

    // -------------------------------------------------------------------------
    // 2. Reject the application — this is the one case that refunds
    // -------------------------------------------------------------------------

    public function rejectApplication(WorkSubmission $submission, string $reason): WorkSubmission
    {
        if (! $submission->isAwaitingApplicationReview()) {
            throw new ApplicationException('This application has already been reviewed.');
        }

        return DB::transaction(function () use ($submission, $reason) {
            $submission->update([
                'application_status' => WorkSubmission::APP_REJECTED,
                'delivery_status'    => WorkSubmission::DEL_NOT_STARTED,
                'rejection_reason'   => $reason,
                'is_read'            => 1,
            ]);

            // NO REFUND. The application fee is non-refundable in every case:
            // rejected by an admin, rejected by the batch draw, or rejected on
            // quality. This used to refund on admin rejection, which was the only
            // refund path in the platform — removing it means the fee is now
            // genuinely non-refundable everywhere, with no exception to explain.
            //
            // Worth being deliberate about: the worker paid to be considered and was
            // not selected. Whether that reads as fair depends entirely on how the
            // fee is described to them BEFORE they pay it, not on what happens after.
            $fee = (float) $submission->fee_paid;

            ActivityLogger::logMoney(
                'task.application.reject',
                $submission,
                0.0,
                $submission->worker_id,
                ['reason' => $reason, 'fee_paid' => $fee, 'fee_refunded' => false]
            );

            $this->notifyWorker($submission, 'SUBMISSION_REJECTED', [
                'work_title' => $submission->work->title ?? '',
                'reason'     => $reason,
            ]);

            return $submission;
        });
    }

    // -------------------------------------------------------------------------
    // 3. Ask for a revision
    // -------------------------------------------------------------------------

    public function requestRevision(WorkSubmission $submission, string $notes): WorkSubmission
    {
        if ($submission->delivery_status !== WorkSubmission::DEL_SUBMITTED) {
            throw new ApplicationException('Only submitted work can be sent back for revision.');
        }

        $max = (int) config('jobstation.max_revisions', 3);
        if ($max > 0 && $submission->revision_count >= $max) {
            throw new ApplicationException(
                "This submission has already used all {$max} revision rounds. Approve or reject it."
            );
        }

        return DB::transaction(function () use ($submission, $notes) {
            $work  = $submission->work;
            $hours = $work ? $work->review_window_hours : (int) config('jobstation.task_review_hours', 48);

            $submission->update([
                'delivery_status'  => WorkSubmission::DEL_REVISION_REQUESTED,
                'rejection_reason' => $notes,
                'revision_count'   => $submission->revision_count + 1,
                // Fresh clock for the worker to fix it.
                'deadline_at'      => now()->addHours($hours),
                'is_read'          => 1,
            ]);

            ActivityLogger::log('task.revision.request', $submission, [
                'revision_count' => $submission->revision_count,
                'notes'          => $notes,
            ]);

            $this->notifyWorker($submission, 'SUBMISSION_REVISION', [
                'work_title' => $work->title ?? '',
                'reason'     => $notes,
                'deadline'   => $submission->deadline_at?->toDayDateTimeString() ?? '',
            ]);

            return $submission;
        });
    }

    // -------------------------------------------------------------------------
    // 4. Approve the delivered work and pay
    // -------------------------------------------------------------------------

    public function approveSubmission(WorkSubmission $submission, bool $isAuto = false): WorkSubmission
    {
        if ($submission->delivery_status === WorkSubmission::DEL_APPROVED) {
            throw new ApplicationException('This work has already been approved.');
        }

        if ($submission->application_status !== WorkSubmission::APP_APPROVED) {
            throw new ApplicationException('This worker was never approved onto the task.');
        }

        return DB::transaction(function () use ($submission, $isAuto) {
            // Re-read under lock. Guards against the cron job and an admin clicking
            // approve at the same moment, which would otherwise pay twice.
            $locked = WorkSubmission::whereKey($submission->id)->lockForUpdate()->firstOrFail();

            if ($locked->delivery_status === WorkSubmission::DEL_APPROVED) {
                throw new ApplicationException('This work has already been approved.');
            }

            // Second guard, on the money rather than the status. delivery_status is a
            // workflow field that a future migration or a manual fix could move;
            // credited_at exists for one purpose and is set in the same transaction
            // as the payout, so "was this paid?" has an answer that cannot drift from
            // whether it actually was.
            if ($locked->credited_at !== null) {
                throw new ApplicationException('This work was already credited at ' . $locked->credited_at->toDateTimeString() . '.');
            }

            $work     = $locked->work;
            $category = $work?->category;

            // Workers are paid in USD, from an amount admin sets per task. Not
            // derived from coins_per_worker and not converted from it: coins and
            // USD never exchange. coins_per_worker still drives the legacy
            // user-gig flow, so it is left alone.
            $gross    = (float) ($work->payout_usd ?? 0);

            $commission = $category ? $category->calculateCommission($gross) : 0.0;
            // 4dp, matching users.usd_balance. Rounding each share to 2dp loses
            // cents against the gross and breaks the reconciliation test.
            $net        = round($gross - $commission, 4);

            $locked->update([
                'delivery_status' => WorkSubmission::DEL_APPROVED,
                'credited_at'     => now(),
                'submitted_at'    => $locked->submitted_at ?? now(),
                'is_read'         => 1,
            ]);

            $worker = User::find($locked->worker_id);

            if ($worker && $net > 0) {
                $reference = generateReference();

                // Two ledger rows for one payout: the worker's net credit, and the
                // platform's commission. Written in the same transaction so the
                // split always reconciles back to $gross.
                EarningsService::credit(
                    $worker,
                    $net,
                    $reference,
                    'work_earn',
                    'Earned: ' . ($work->title ?? 'task')
                        . ($commission > 0 ? ' (after ' . rtrim(rtrim(number_format($category->commission_percent, 2), '0'), '.') . '% commission)' : '')
                );

                if ($commission > 0) {
                    EarningsService::recordCommission(
                        $commission,
                        $reference,
                        'Commission: ' . ($work->title ?? 'task'),
                        $worker->id
                    );
                }
            }

            // Close the task once every slot has been delivered and approved.
            if ($work && $work->approvedSubmissions()->count() >= $work->worker_slots) {
                $work->update(['work_status' => 2]);
            }

            ActivityLogger::logMoney(
                $isAuto ? 'task.submission.auto_approve' : 'task.submission.approve',
                $locked,
                $net,
                $locked->worker_id,
                [
                    'gross'      => $gross,
                    'commission' => $commission,
                    'net'        => $net,
                    'auto'       => $isAuto,
                ]
            );

            $locked->setRelation('work', $work);
            $this->notifyWorker($locked, 'SUBMISSION_APPROVED', [
                'work_title' => $work->title ?? '',
                'coins'      => formatCoins($net),
            ]);

            return $locked;
        });
    }

    // -------------------------------------------------------------------------
    // 5. Reject the delivered work — no refund
    // -------------------------------------------------------------------------

    public function rejectSubmission(WorkSubmission $submission, string $reason): WorkSubmission
    {
        if ($submission->delivery_status === WorkSubmission::DEL_APPROVED) {
            throw new ApplicationException('Cannot reject work that has already been paid.');
        }

        if ($submission->application_status !== WorkSubmission::APP_APPROVED) {
            throw new ApplicationException('This worker was never approved onto the task.');
        }

        return DB::transaction(function () use ($submission, $reason) {
            $submission->update([
                'delivery_status'  => WorkSubmission::DEL_REJECTED,
                'rejection_reason' => $reason,
                'is_read'          => 1,
            ]);

            // No refund by design. The worker occupied a slot and consumed review
            // time, so the application fee stays with the platform.
            ActivityLogger::log('task.submission.reject', $submission, [
                'reason'       => $reason,
                'fee_retained' => (float) $submission->fee_paid,
            ]);

            $this->notifyWorker($submission, 'SUBMISSION_REJECTED', [
                'work_title' => $submission->work->title ?? '',
                'reason'     => $reason,
            ]);

            return $submission;
        });
    }

    // -------------------------------------------------------------------------
    // 6. Worker never delivered — cancel, free the slot, keep the fee
    // -------------------------------------------------------------------------

    public function expireAbandoned(WorkSubmission $submission): WorkSubmission
    {
        return DB::transaction(function () use ($submission) {
            $submission->update([
                'delivery_status'  => WorkSubmission::DEL_EXPIRED,
                'rejection_reason' => 'Deadline passed with no submission.',
                'is_read'          => 1,
            ]);

            // Marking it expired takes it out of occupyingSubmissions(), which is
            // what frees the slot. No refund.
            ActivityLogger::log('task.submission.expire', $submission, [
                'deadline_at'  => $submission->deadline_at?->toDateTimeString(),
                'fee_retained' => (float) $submission->fee_paid,
                'actor'        => 'system',
            ]);

            $this->notifyWorker($submission, 'SUBMISSION_REJECTED', [
                'work_title' => $submission->work->title ?? '',
                'reason'     => 'You did not submit before the deadline, so the slot was released.',
            ]);

            return $submission;
        });
    }

    // -------------------------------------------------------------------------

    protected function notifyWorker(WorkSubmission $submission, string $act, array $data): void
    {
        try {
            $worker = User::find($submission->worker_id);
            if ($worker) {
                NotifyService::send($worker, $act, $data);
            }
        } catch (\Throwable $e) {
            // A failed email must never roll back a paid-out submission.
            \Illuminate\Support\Facades\Log::warning('Task notification failed', [
                'submission_id' => $submission->id,
                'act'           => $act,
                'error'         => $e->getMessage(),
            ]);
        }
    }
}
