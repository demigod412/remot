<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerEntry extends Model
{
    public const SYSTEM_ACCOUNT_ID = 0;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'currency',
        'reference',
        'description',
        'balance_after',
    ];

    protected $casts = [
        'amount'        => 'decimal:4',
        'balance_after' => 'decimal:4',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault([
            'name'  => 'Platform (System)',
            'email' => null,
        ]);
    }

    public function isSystemAccount(): bool
    {
        return (int) $this->user_id === self::SYSTEM_ACCOUNT_ID;
    }

    public function scopeCoin($query)
    {
        return $query->where('currency', 'coin');
    }

    public function scopeUsd($query)
    {
        return $query->where('currency', 'usd');
    }

    public function scopeForReference($query, string $reference)
    {
        return $query->where('reference', $reference);
    }
}