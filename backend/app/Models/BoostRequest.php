<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BoostRequest extends Model
{
    protected $fillable = [
        'boostable_type', 'boostable_id', 'user_id',
        'days', 'coins_paid', 'status', 'rejection_reason', 'approved_at',
    ];

    protected $casts = [
        'status'      => 'integer',
        'days'        => 'integer',
        'coins_paid'  => 'decimal:8',
        'approved_at' => 'datetime',
    ];

    // 0 = pending, 1 = approved, 2 = rejected

    public function boostable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($q)
    {
        return $q->where('status', 0);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            0       => 'Pending',
            1       => 'Approved',
            2       => 'Rejected',
            default => 'Unknown',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            0       => 'badge-warning',
            1       => 'badge-success',
            2       => 'badge-danger',
            default => 'badge-default',
        };
    }
}
