<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentChannel extends Model
{
    protected $fillable = [
        'form_id', 'code', 'name', 'driver', 'status',
        'credentials', 'currencies', 'is_crypto', 'webhook_info', 'instructions',
    ];

    protected function casts(): array
    {
        return [
            'credentials'  => 'array',
            'currencies'   => 'array',
            'webhook_info' => 'array',
            'is_crypto'    => 'boolean',
        ];
    }

    public function rates()
    {
        return $this->hasMany(PaymentChannelRate::class, 'channel_code', 'code');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeAutomatic($query)
    {
        return $query->where('driver', '!=', 'manual');
    }

    public function scopeManual($query)
    {
        return $query->where('driver', 'manual');
    }

    public function scopeCrypto($query)
    {
        return $query->where('is_crypto', 1);
    }

    /**
     * Manual = offline (bank transfer / cash) where the user uploads proof.
     * Everything else is an automatic gateway driver (Stripe, PayPal, …).
     * Keyed on the driver, not the code, so automatic gateways are never
     * mistaken for manual regardless of their channel code.
     */
    public function getIsManualAttribute(): bool
    {
        return strtolower((string) $this->driver) === 'manual';
    }
}
