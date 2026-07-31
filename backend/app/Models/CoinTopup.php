<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoinTopup extends Model
{
    protected $fillable = [
        'user_id', 'channel_code', 'amount', 'pay_currency',
        'charge', 'rate', 'payable_amount', 'gateway_response',
        'crypto_amount', 'crypto_address', 'reference', 'attempts',
        'status', 'via_api', 'admin_note', 'coins_credited', 'package_id',
        'proof_image', 'transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'amount'         => 'decimal:8',
            'charge'         => 'decimal:8',
            'rate'           => 'decimal:8',
            'payable_amount' => 'decimal:8',
            'coins_credited' => 'decimal:8',
            'via_api'        => 'boolean',
            'status'         => 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function channel()
    {
        return $this->belongsTo(PaymentChannel::class, 'channel_code', 'code');
    }

    public function package()
    {
        return $this->belongsTo(CoinPackage::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeInitiated($query)
    {
        return $query->where('status', 0);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 1);
    }

    public function scopePending($query)
    {
        return $query->where('status', 2);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 3);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            0 => 'Initiated',
            1 => 'Successful',
            2 => 'Pending',
            3 => 'Cancelled',
            default => 'Unknown',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            0 => 'blue',
            1 => 'green',
            2 => 'yellow',
            3 => 'red',
            default => 'gray',
        };
    }
}
