<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cashout extends Model
{
    protected $fillable = [
        'user_id', 'payout_method_id', 'coin_amount', 'payout_currency',
        'coin_to_currency_rate', 'fee', 'reference', 'gateway_reference', 'payout_amount',
        'net_coins_deducted', 'payout_details', 'status', 'admin_note',
        'cancelled_at', 'cancelled_reason',
    ];

    protected function casts(): array
    {
        return [
            'status'                => 'integer',
            'coin_amount'           => 'decimal:8',
            'coin_to_currency_rate' => 'decimal:8',
            'fee'                   => 'decimal:8',
            'payout_amount'         => 'decimal:8',
            'net_coins_deducted'    => 'decimal:8',
            'payout_details'        => 'array',
            'cancelled_at'          => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payoutMethod()
    {
        return $this->belongsTo(PayoutMethod::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopePending($query)
    {
        return $query->where('status', 0);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 1);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 2);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            0 => 'Pending',
            1 => 'Approved',
            2 => 'Rejected',
            3 => 'Disbursing',
            4 => 'Failed',
            default => 'Unknown',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            0 => 'yellow',
            1 => 'green',
            2 => 'red',
            3 => 'blue',
            4 => 'red',
            default => 'gray',
        };
    }
}
