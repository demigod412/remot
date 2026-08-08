<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkSubmission extends Model
{
    protected $fillable = [
        'work_id', 'work_poster_id', 'work_poster_type',
        'worker_id', 'worker_type',
        'proof_files', 'proof_note', 'rejection_reason',
        'status', 'is_read', 'submitted_at', 'deadline_at',
        // Two-axis lifecycle (migration 0067)
        'application_status', 'delivery_status',
        'result_payload', 'progress_payload', 'progress_saved_at',
        'review_deadline', 'credited_at', 'annotate_code', 'approved_by_batch',
        'task_files', 'task_instructions', 'task_delivered_at', 'revision_count',
        'fee_paid', 'fee_reference',
    ];

    protected function casts(): array
    {
        return [
            'proof_files'        => 'array',
            'task_files'         => 'array',
            'result_payload'     => 'array',
            'progress_payload'   => 'array',
            'review_deadline'    => 'datetime',
            'credited_at'        => 'datetime',
            'progress_saved_at'  => 'datetime',
            'submitted_at'       => 'datetime',
            'deadline_at'        => 'datetime',
            'task_delivered_at'  => 'datetime',
            'is_read'            => 'boolean',
            'status'             => 'integer',
            'application_status' => 'integer',
            'delivery_status'    => 'integer',
            'revision_count'     => 'integer',
            'fee_paid'           => 'decimal:2',
            'worker_type'        => 'integer',
            'work_poster_type'   => 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // Lifecycle constants
    // -------------------------------------------------------------------------

    public const APP_APPLIED   = 0;
    public const APP_APPROVED  = 1;
    public const APP_REJECTED  = 2;

    public const DEL_NOT_STARTED       = 0;
    public const DEL_SUBMITTED         = 1;
    public const DEL_REVISION_REQUESTED = 2;
    public const DEL_APPROVED          = 3;
    public const DEL_REJECTED          = 4;
    public const DEL_EXPIRED           = 5;

    // Legacy single-column values, kept only for the mirror below.
    public const LEGACY_APPLIED      = 0;
    public const LEGACY_UNDER_REVIEW = 1;
    public const LEGACY_APPROVED     = 2;
    public const LEGACY_REJECTED     = 3;

    // -------------------------------------------------------------------------
    // Legacy status mirror
    //
    // `status` is a real column, not an accessor, and a lot of untouched code
    // queries it directly (scopes below, ProcessWorkTimers, admin reports). An
    // accessor cannot rescue a where() clause, so instead we keep the column
    // populated as a derived mirror on every save.
    //
    // The mapping is lossy on purpose. Legacy code only ever understood four
    // states, so the three "in progress" delivery states all collapse to
    // LEGACY_UNDER_REVIEW, which is the closest thing it knew about.
    //
    // Read from application_status / delivery_status in all NEW code. Treat
    // `status` as write-only, derived, and on its way out.
    // -------------------------------------------------------------------------

    protected static function booted(): void
    {
        static::saving(function (self $submission) {
            $submission->status = $submission->deriveLegacyStatus();
        });
    }

    public function deriveLegacyStatus(): int
    {
        $application = (int) ($this->application_status ?? self::APP_APPLIED);
        $delivery    = (int) ($this->delivery_status ?? self::DEL_NOT_STARTED);

        if ($application === self::APP_APPLIED) {
            return self::LEGACY_APPLIED;
        }

        if ($application === self::APP_REJECTED) {
            return self::LEGACY_REJECTED;
        }

        // Application approved. The delivery axis decides.
        return match ($delivery) {
            self::DEL_APPROVED => self::LEGACY_APPROVED,
            self::DEL_REJECTED, self::DEL_EXPIRED => self::LEGACY_REJECTED,
            default            => self::LEGACY_UNDER_REVIEW,
        };
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function work()
    {
        return $this->belongsTo(Work::class);
    }

    public function worker()
    {
        if ($this->worker_type === 1) {
            return $this->belongsTo(Admin::class, 'worker_id');
        }
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function workPoster()
    {
        if ($this->work_poster_type === 1) {
            return $this->belongsTo(Admin::class, 'work_poster_id');
        }
        return $this->belongsTo(User::class, 'work_poster_id');
    }

    // -------------------------------------------------------------------------
    // Legacy scopes
    //
    // Left querying `status` deliberately. The mirror above keeps them correct,
    // so existing callers do not change behaviour. Prefer the new scopes below
    // for anything you are writing now.
    // -------------------------------------------------------------------------

    public function scopeApplied($query)
    {
        return $query->where('status', self::LEGACY_APPLIED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::LEGACY_UNDER_REVIEW);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::LEGACY_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::LEGACY_REJECTED);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', 0);
    }

    // -------------------------------------------------------------------------
    // New lifecycle scopes
    // -------------------------------------------------------------------------

    public function scopeAwaitingApplicationReview($query)
    {
        return $query->where('application_status', self::APP_APPLIED);
    }

    public function scopeApprovedToWork($query)
    {
        return $query->where('application_status', self::APP_APPROVED);
    }

    public function scopeApplicationRejected($query)
    {
        return $query->where('application_status', self::APP_REJECTED);
    }

    public function scopeNotStarted($query)
    {
        return $query->where('application_status', self::APP_APPROVED)
                     ->where('delivery_status', self::DEL_NOT_STARTED);
    }

    public function scopeAwaitingDeliveryReview($query)
    {
        return $query->where('application_status', self::APP_APPROVED)
                     ->where('delivery_status', self::DEL_SUBMITTED);
    }

    public function scopeRevisionRequested($query)
    {
        return $query->where('application_status', self::APP_APPROVED)
                     ->where('delivery_status', self::DEL_REVISION_REQUESTED);
    }

    public function scopeDeliveryApproved($query)
    {
        return $query->where('application_status', self::APP_APPROVED)
                     ->where('delivery_status', self::DEL_APPROVED);
    }

    public function scopeExpired($query)
    {
        return $query->where('delivery_status', self::DEL_EXPIRED);
    }

    /**
     * Approved to work but never delivered, past the worker deadline. These get
     * cancelled by ProcessWorkTimers: the slot is freed and the application fee
     * is NOT refunded.
     */
    public function scopeAbandoned($query)
    {
        return $query->where('application_status', self::APP_APPROVED)
                     ->whereIn('delivery_status', [self::DEL_NOT_STARTED, self::DEL_REVISION_REQUESTED])
                     ->whereNotNull('deadline_at')
                     ->where('deadline_at', '<=', now());
    }

    /**
     * Applications that still occupy one of the task's worker_slots.
     *
     * A rejected application or an expired/abandoned one releases its slot so
     * somebody else can take it. Everything else is holding a place.
     */
    public function scopeOccupyingSlot($query)
    {
        return $query->where('application_status', '!=', self::APP_REJECTED)
                     ->where('delivery_status', '!=', self::DEL_EXPIRED);
    }

    public function scopeDeliveryRejected($query)
    {
        return $query->where('application_status', self::APP_APPROVED)
                     ->where('delivery_status', self::DEL_REJECTED);
    }

    /**
     * Submitted work that admin has not acted on and whose deadline has passed.
     * This is the set ProcessWorkTimers auto-approves.
     */
    public function scopeOverdueForAutoApproval($query)
    {
        return $query->where('application_status', self::APP_APPROVED)
                     ->where('delivery_status', self::DEL_SUBMITTED)
                     ->whereNotNull('deadline_at')
                     ->where('deadline_at', '<=', now());
    }

    // -------------------------------------------------------------------------
    // State questions
    // -------------------------------------------------------------------------

    public function isAwaitingApplicationReview(): bool
    {
        return $this->application_status === self::APP_APPLIED;
    }

    public function isApprovedToWork(): bool
    {
        return $this->application_status === self::APP_APPROVED;
    }

    /**
     * True while the worker still owes admin a deliverable.
     */
    public function isOpenForWorker(): bool
    {
        return $this->application_status === self::APP_APPROVED
            && in_array($this->delivery_status, [
                self::DEL_NOT_STARTED,
                self::DEL_REVISION_REQUESTED,
            ], true);
    }

    /**
     * True once no further state change is expected.
     */
    public function isSettled(): bool
    {
        return $this->application_status === self::APP_REJECTED
            || in_array($this->delivery_status, [
                self::DEL_APPROVED,
                self::DEL_REJECTED,
            ], true);
    }

    /**
     * A rejected application is the only case that gets an automatic coin refund.
     * Rejected *work* does not, because the worker consumed the slot.
     */
    public function isRefundable(): bool
    {
        return $this->application_status === self::APP_REJECTED
            && (float) $this->fee_paid > 0
            && ! empty($this->fee_reference);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getApplicationStatusLabelAttribute(): string
    {
        return match ($this->application_status) {
            self::APP_APPLIED  => 'Awaiting Review',
            self::APP_APPROVED => 'Approved To Work',
            self::APP_REJECTED => 'Application Rejected',
            default            => 'Unknown',
        };
    }

    public function getDeliveryStatusLabelAttribute(): string
    {
        return match ($this->delivery_status) {
            self::DEL_NOT_STARTED        => 'Not Started',
            self::DEL_SUBMITTED          => 'Submitted',
            self::DEL_REVISION_REQUESTED => 'Revision Requested',
            self::DEL_APPROVED           => 'Approved',
            self::DEL_REJECTED           => 'Rejected',
            self::DEL_EXPIRED            => 'Expired',
            default                      => 'Unknown',
        };
    }

    /**
     * Single line summary for list views, combining both axes.
     */
    public function getLifecycleLabelAttribute(): string
    {
        if ($this->application_status !== self::APP_APPROVED) {
            return $this->application_status_label;
        }

        return $this->delivery_status_label;
    }

    public function getLifecycleColorAttribute(): string
    {
        if ($this->application_status === self::APP_APPLIED) {
            return 'blue';
        }
        if ($this->application_status === self::APP_REJECTED) {
            return 'red';
        }

        return match ($this->delivery_status) {
            self::DEL_NOT_STARTED        => 'gray',
            self::DEL_SUBMITTED          => 'yellow',
            self::DEL_REVISION_REQUESTED => 'orange',
            self::DEL_APPROVED           => 'green',
            self::DEL_REJECTED           => 'red',
            self::DEL_EXPIRED            => 'gray',
            default                      => 'gray',
        };
    }

    // Kept for any Blade still referencing status_label / status_color.
    public function getStatusLabelAttribute(): string
    {
        return $this->lifecycle_label;
    }

    public function getStatusColorAttribute(): string
    {
        return $this->lifecycle_color;
    }
}
