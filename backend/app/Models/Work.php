<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Work extends Model
{
    protected $fillable = [
        'poster_id', 'poster_type', 'category_id', 'subcategory_id',
        'topup_id', 'slug', 'title', 'cover_image', 'worker_slots',
        'display_application_boost',
        'payout_usd',
        'description', 'total_coins', 'coins_per_worker', 'avg_minutes',
        'work_status', 'approval_status', 'rejection_reason',
        'expires_at', 'auto_approve_hours',
        'is_featured', 'featured_until',
        'allow_multiple_submissions', 'requires_kyc',
    ];

    protected function casts(): array
    {
        return [
            'total_coins'      => 'decimal:2',
            'coins_per_worker' => 'decimal:2',
            'expires_at'       => 'datetime',
            'featured_until'   => 'datetime',
            'is_featured'                => 'boolean',
            'allow_multiple_submissions' => 'boolean',
            'requires_kyc'              => 'boolean',
            'work_status'      => 'integer',
            'approval_status'  => 'integer',
            'poster_type'      => 'integer',
            'poster_id'        => 'integer',
            'worker_slots'     => 'integer',
            'display_application_boost' => 'integer',
            'payout_usd'                => 'decimal:4',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'work_skills');
    }

    public function boostRequests()
    {
        return $this->morphMany(BoostRequest::class, 'boostable');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)
                     ->where(function ($q) {
                         $q->whereNull('featured_until')
                           ->orWhere('featured_until', '>', now());
                     });
    }

    public function poster()
    {
        if ($this->poster_type === 1) {
            return $this->belongsTo(Admin::class, 'poster_id');
        }
        return $this->belongsTo(User::class, 'poster_id');
    }

    public function category()
    {
        return $this->belongsTo(WorkCategory::class, 'category_id');
    }

    public function subcategory()
    {
        return $this->belongsTo(WorkSubcategory::class, 'subcategory_id');
    }

    public function bookmarks()
    {
        return $this->hasMany(WorkBookmark::class);
    }

    public function submissions()
    {
        return $this->hasMany(WorkSubmission::class);
    }

    public function approvedSubmissions()
    {
        return $this->hasMany(WorkSubmission::class)
                    ->where('application_status', WorkSubmission::APP_APPROVED)
                    ->where('delivery_status', WorkSubmission::DEL_APPROVED);
    }

    /**
     * Applications currently holding one of this task's worker_slots.
     *
     * Slots cap TOTAL applications, not just approved ones, otherwise a task with
     * 5 slots can accumulate hundreds of pending applicants who have each paid a
     * fee they will never get to earn against.
     *
     * Rejected applications and expired ones release their slot again.
     */
    public function occupyingSubmissions()
    {
        return $this->hasMany(WorkSubmission::class)
                    ->where('application_status', '!=', WorkSubmission::APP_REJECTED)
                    ->where('delivery_status', '!=', WorkSubmission::DEL_EXPIRED);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeApproved($query)
    {
        return $query->where('approval_status', 1);
    }

    public function scopePending($query)
    {
        return $query->where('approval_status', 0);
    }

    public function scopeRejected($query)
    {
        return $query->where('approval_status', 2);
    }

    public function scopeActive($query)
    {
        return $query->where('work_status', 1)->where('approval_status', 1);
    }

    public function scopeHolding($query)
    {
        return $query->where('work_status', 0);
    }

    public function scopeFinished($query)
    {
        return $query->where('work_status', 2);
    }

    public function scopePostedByUser($query)
    {
        return $query->where('poster_type', 2);
    }

    public function scopePostedByAdmin($query)
    {
        return $query->where('poster_type', 1);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getWorkStatusLabelAttribute(): string
    {
        return match ($this->work_status) {
            0 => 'Holding',
            1 => 'Active',
            2 => 'Finished',
            default => 'Unknown',
        };
    }

    public function getApprovalStatusLabelAttribute(): string
    {
        return match ($this->approval_status) {
            0 => 'Pending',
            1 => 'Approved',
            2 => 'Rejected',
            default => 'Unknown',
        };
    }

    /**
     * Free slots, counting ALL live applications rather than only approved work.
     * See occupyingSubmissions() for why.
     */
    public function getSlotsRemainingAttribute(): int
    {
        return max(0, $this->worker_slots - $this->occupyingSubmissions()->count());
    }

    /**
     * Coins a worker must spend to apply, inherited from the category.
     */
    public function getApplicationCostAttribute(): float
    {
        return (float) ($this->category->application_cost ?? 0);
    }

    /**
     * Platform commission percent for this task, inherited from the category.
     * Not overridable per task by design.
     */
    public function getCommissionPercentAttribute(): float
    {
        return (float) ($this->category->commission_percent ?? 0);
    }

    public function getCommissionAmountAttribute(): float
    {
        return $this->category
            ? $this->category->calculateCommission((float) $this->coins_per_worker)
            : 0.0;
    }

    public function getNetPayoutAttribute(): float
    {
        return $this->category
            ? $this->category->calculateNetPayout((float) $this->coins_per_worker)
            : (float) $this->coins_per_worker;
    }

    /**
     * Hours a worker gets to deliver once admin hands them the task, and hours
     * admin gets to review a delivery before it auto-approves. Same window,
     * falling back to the configured default when the task does not set one.
     */
    public function getReviewWindowHoursAttribute(): int
    {
        return (int) ($this->auto_approve_hours ?: config('jobstation.task_review_hours', 48));
    }

    public function getIsHotAttribute(): bool
    {
        if ($this->worker_slots <= 0) return false;
        return ($this->occupyingSubmissions()->count() / $this->worker_slots) >= 0.8;
    }

    /**
     * Real number of live applications. This is the authoritative figure and the
     * one every slot decision uses.
     */
    public function getRealApplicationCountAttribute(): int
    {
        return $this->occupyingSubmissions()->count();
    }

    /**
     * Applicant count to SHOW on the public task page.
     *
     * Includes display_application_boost, a cosmetic head start so a freshly
     * posted task does not read as dead. Never use this for anything but display:
     * it is not the real count and it must not reach slot arithmetic, payouts or
     * the admin review queue.
     */
    public function getDisplayApplicationCountAttribute(): int
    {
        return $this->real_application_count + (int) $this->display_application_boost;
    }

    /**
     * Slot total to SHOW alongside display_application_count.
     *
     * Both the shown applicant count and this total carry the same boost, so the
     * difference between them is the REAL number of free slots:
     *
     *     shown applied   = real applied + boost
     *     shown total     = real slots   + boost
     *     shown remaining = shown total - shown applied = real slots - real applied
     *
     * That is the entire point. The page reads busier without ever claiming fewer
     * places remain than actually do, which keeps a scarcity claim off a page that
     * charges a non-refundable application fee.
     *
     * Display only. Real capacity is worker_slots and nothing here touches slot
     * arithmetic, which is why the boost tests still pass unchanged.
     */
    public function getDisplaySlotTotalAttribute(): int
    {
        return (int) $this->worker_slots + (int) $this->display_application_boost;
    }

    /**
     * True when this task is admin-posted. With user gigs disabled everything new
     * should be admin-posted, but legacy user tasks still exist in the data.
     */
    public function getIsAdminPostedAttribute(): bool
    {
        return $this->poster_type === 1;
    }
}
