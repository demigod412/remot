<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $fillable = [
        'employer_id', 'worker_id',
        'title', 'description', 'amount', 'commission_amount', 'worker_payout', 'deadline_at',
        'status', 'reference',
        'worker_note', 'proof_file',
        'employer_note', 'declined_reason',
        'accepted_at', 'submitted_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status'            => 'integer',
            'employer_id'       => 'integer',
            'worker_id'         => 'integer',
            'amount'            => 'decimal:8',
            'commission_amount' => 'decimal:8',
            'worker_payout'     => 'decimal:8',
            'deadline_at'       => 'datetime',
            'accepted_at'  => 'datetime',
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────

    public function employer()
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function milestones()
    {
        return $this->hasMany(ContractMilestone::class)->orderBy('sort_order');
    }

    public function hasMilestones(): bool
    {
        return $this->milestones()->exists();
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->whereIn('status', [1, 2]);
    }

    public function scopePending($query)
    {
        return $query->where('status', 0);
    }

    // ── Accessors ─────────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return [
            0 => 'Offered',
            1 => 'Accepted',
            2 => 'Submitted',
            3 => 'Completed',
            4 => 'Declined',
            5 => 'Cancelled',
            6 => 'Disputed',
        ][$this->status] ?? 'Unknown';
    }

    public function getStatusColorAttribute(): string
    {
        return [
            0 => 'badge-warning',
            1 => 'badge-info',
            2 => 'badge-purple',
            3 => 'badge-success',
            4 => 'badge-danger',
            5 => 'badge-default',
            6 => 'badge-danger',
        ][$this->status] ?? 'badge-default';
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->deadline_at
            && $this->deadline_at->isPast()
            && in_array($this->status, [1, 2]);
    }

    // ── Helpers ───────────────────────────────────────────────────

    public static function generateReference(): string
    {
        do {
            $ref = 'CTR-' . strtoupper(\Illuminate\Support\Str::random(8));
        } while (static::where('reference', $ref)->exists());

        return $ref;
    }
}
