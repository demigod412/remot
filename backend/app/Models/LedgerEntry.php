<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LedgerEntry extends Model
{
    /**
     * 'currency' added in migration 0074. It MUST be listed here: without it,
     * create(['currency' => 'usd']) is silently dropped and the row falls back
     * to the column default of 'coin', booking a dollar payout as coins.
     */
    protected $fillable = [
        'user_id', 'coins', 'fee', 'balance_after',
        'entry_type', 'reference', 'description', 'category', 'currency',
    ];

    protected function casts(): array
    {
        return [
            'coins'        => 'decimal:8',
            'fee'          => 'decimal:8',
            'balance_after'=> 'decimal:8',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeCredits($query)
    {
        return $query->where('entry_type', '+');
    }

    public function scopeDebits($query)
    {
        return $query->where('entry_type', '-');
    }

    /**
     * The amount lives in `coins` regardless of currency, so any sum() over the
     * ledger must be scoped to one currency or it adds dollars to coins.
     */
    public function scopeInCoins($query)
    {
        return $query->where('currency', 'coin');
    }

    public function scopeInUsd($query)
    {
        return $query->where('currency', 'usd');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function getIsDebitAttribute(): bool
    {
        return $this->entry_type === '-';
    }

    public function getIsCreditAttribute(): bool
    {
        return $this->entry_type === '+';
    }

    public function getCategoryLabelAttribute(): string
    {
        return config('jobstation.ledger_categories')[$this->category] ?? ucfirst($this->category ?? '');
    }
}
