<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use App\Models\WorkSubmission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TaskReviewService
{
    public const DELIVERY_PENDING   = 'pending';
    public const DELIVERY_DELIVERED = 'delivered';
    public const DELIVERY_APPROVED  = 'approved';
    public const DELIVERY_REJECTED  = 'rejected';
    public const DELIVERY_REVISION  = 'revision';

    public function __construct(private CoinService $coins)
    {
    }

    /**
     * Approve a delivered submission: credit worker USD, log commission to
     * system account, flip statuses, write audit trail.
     * Idempotent — a second call returns without double paying.
     */
    public function approveSubmission(WorkSubmission $submission, ?string $adminNote = null): WorkSubmission
    {
        return DB::transaction(function () use ($submission, $adminNote) {
            /** @var WorkSubmission $locked */
            $locked = WorkSubmission::query()
                ->with(['work.category', 'user'])
                ->whereKey($submission->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->delivery_status === self::DELIVERY_APPROVED) {
                return $locked;
            }

            $work   = $locked->work;
            $worker = $locked->user;

            if (! $work || ! $worker) {
                throw new RuntimeException("Submission #{$locked->id} is missing its task or worker.");
            }

            $split = $this->coins->splitCommission(
                (float) $work->amount,
                (float) ($work->category->commission_percent ?? 0)
            );

            $result = $this->coins->creditTaskEarnings(
                worker: $worker,
                grossAmount: $split['gross'],
                commissionAmount: $split['commission'],
                reference: $this->earningsReference($locked),
                description: "Earnings for task #{$work->id}: {$work->title}",
            );

            $locked->forceFill([
                'delivery_status'   => self::DELIVERY_APPROVED,
                'status'            => $this->legacyStatusFor(self::DELIVERY_APPROVED),
                'gross_amount'      => $split['gross'],
                'commission_amount' => $split['commission'],
                'net_amount'        => $split['net'],
                'admin_note'        => $adminNote,
                'reviewed_by'       => Auth::id(),
                'reviewed_at'       => now(),
            ])->save();

            $this->log($locked, 'task_submission.approved', sprintf(
                'Approved submission #%d for task #%d. Gross %.4f USD, commission %.4f USD, net %.4f USD credited to user #%d.',
                $locked->id,
                $work->id,
                $split['gross'],
                $split['commission'],
                $result['net'],
                $worker->id
            ));

            return $locked->refresh();
        });
    }

    /**
     * Quality rejection. No payout, and no application fee refund —
     * fees are only refunded when an admin rejects the application itself.
     */
    public function rejectSubmission(WorkSubmission $submission, string $reason): WorkSubmission
    {
        return DB::transaction(function () use ($submission, $reason) {
            /** @var WorkSubmission $locked */
            $locked = WorkSubmission::query()
                ->whereKey($submission->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->delivery_status === self::DELIVERY_APPROVED) {
                throw new RuntimeException('Cannot reject a submission that has already been paid out.');
            }

            $locked->forceFill([
                'delivery_status' => self::DELIVERY_REJECTED,
                'status'          => $this->legacyStatusFor(self::DELIVERY_REJECTED),
                'admin_note'      => $reason,
                'reviewed_by'     => Auth::id(),
                'reviewed_at'     => now(),
            ])->save();

            $this->log(
                $locked,
                'task_submission.rejected',
                "Rejected submission #{$locked->id} for quality. No fee refund issued. Reason: {$reason}"
            );

            return $locked;
        });
    }

    public function requestRevision(WorkSubmission $submission, string $note): WorkSubmission
    {
        return DB::transaction(function () use ($submission, $note) {
            /** @var WorkSubmission $locked */
            $locked = WorkSubmission::query()
                ->whereKey($submission->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->delivery_status === self::DELIVERY_APPROVED) {
                throw new RuntimeException('Cannot request revision on a submission that has already been paid out.');
            }

            $locked->forceFill([
                'delivery_status' => self::DELIVERY_REVISION,
                'status'          => $this->legacyStatusFor(self::DELIVERY_REVISION),
                'admin_note'      => $note,
                'reviewed_by'     => Auth::id(),
                'reviewed_at'     => now(),
            ])->save();

            $this->log(
                $locked,
                'task_submission.revision_requested',
                "Requested revision on submission #{$locked->id}: {$note}"
            );

            return $locked;
        });
    }

    public function markDelivered(WorkSubmission $submission): WorkSubmission
    {
        $submission->forceFill([
            'delivery_status' => self::DELIVERY_DELIVERED,
            'status'          => $this->legacyStatusFor(self::DELIVERY_DELIVERED),
            'delivered_at'    => now(),
        ])->save();

        $this->log(
            $submission,
            'task_submission.delivered',
            "Marked submission #{$submission->id} as delivered and ready for review."
        );

        return $submission;
    }

    public function earningsReference(WorkSubmission $submission): string
    {
        return "task_submission_{$submission->id}";
    }

    private function legacyStatusFor(string $deliveryStatus): int
    {
        return match ($deliveryStatus) {
            self::DELIVERY_DELIVERED => 1,
            self::DELIVERY_APPROVED  => 2,
            self::DELIVERY_REJECTED  => 3,
            self::DELIVERY_REVISION  => 4,
            default                  => 0,
        };
    }

    private function log(WorkSubmission $submission, string $action, string $description): void
    {
        AdminActivityLog::create([
            'admin_id'     => Auth::id(),
            'action'       => $action,
            'subject_type' => WorkSubmission::class,
            'subject_id'   => $submission->id,
            'description'  => $description,
            'ip_address'   => request()?->ip(),
            'user_agent'   => request()?->userAgent(),
        ]);
    }
}