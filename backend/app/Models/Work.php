<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Work extends Model
{
    protected $fillable = [
        'poster_id', 'poster_type', 'category_id', 'subcategory_id',
        'topup_id', 'slug', 'title', 'cover_image', 'worker_slots',
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
        return $this->hasMany(WorkSubmission::class)->where('status', 2);
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

    public function getSlotsRemainingAttribute(): int
    {
        return max(0, $this->worker_slots - $this->approvedSubmissions()->count());
    }

    public function getIsHotAttribute(): bool
    {
        if ($this->worker_slots <= 0) return false;
        return ($this->submissions()->count() / $this->worker_slots) >= 0.8;
    }
}
