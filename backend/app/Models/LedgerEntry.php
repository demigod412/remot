<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LedgerEntry extends Model
{
    protected $fillable = [
        'user_id', 'coins', 'fee', 'balance_after',
        'entry_type', 'reference', 'description', 'category',
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
