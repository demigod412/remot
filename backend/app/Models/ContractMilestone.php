<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractMilestone extends Model
{
    protected $table = 'contract_milestones';

    protected $fillable = [
        'contract_id', 'sort_order', 'title', 'description',
        'amount', 'deadline_at', 'status',
        'worker_note', 'proof_file',
        'commission_amount', 'worker_payout',
        'submitted_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'            => 'decimal:8',
            'commission_amount' => 'decimal:8',
            'worker_payout'     => 'decimal:8',
            'status'            => 'integer',
            'sort_order'        => 'integer',
            'deadline_at'       => 'datetime',
            'submitted_at'      => 'datetime',
            'completed_at'      => 'datetime',
        ];
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return [0 => 'Pending', 1 => 'Submitted', 2 => 'Approved', 3 => 'Disputed'][$this->status] ?? 'Unknown';
    }

    public function getStatusColorAttribute(): string
    {
        return [0 => 'var(--fg-3)', 1 => '#60A5FA', 2 => '#22C55E', 3 => '#EF4444'][$this->status] ?? 'var(--fg-3)';
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->deadline_at && $this->deadline_at->isPast() && $this->status < 2;
    }
}
