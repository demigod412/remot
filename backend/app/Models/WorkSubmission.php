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
    ];

    protected function casts(): array
    {
        return [
            'proof_files'       => 'array',
            'submitted_at'      => 'datetime',
            'deadline_at'       => 'datetime',
            'is_read'           => 'boolean',
            'status'            => 'integer',
            'worker_type'       => 'integer',
            'work_poster_type'  => 'integer',
        ];
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
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeApplied($query)
    {
        return $query->where('status', 0);
    }

    public function scopePending($query)
    {
        return $query->where('status', 1);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 2);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 3);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', 0);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            0 => 'Applied',
            1 => 'Under Review',
            2 => 'Approved',
            3 => 'Rejected',
            default => 'Unknown',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            0 => 'blue',
            1 => 'yellow',
            2 => 'green',
            3 => 'red',
            default => 'gray',
        };
    }
}
