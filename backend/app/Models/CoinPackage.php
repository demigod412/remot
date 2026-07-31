<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoinPackage extends Model
{
    protected $fillable = [
        'name', 'coins', 'price', 'currency',
        'bonus_coins', 'badge_label', 'is_popular', 'status',
    ];

    protected function casts(): array
    {
        return [
            'is_popular' => 'boolean',
            'price'      => 'decimal:2',
        ];
    }

    public function getTotalCoinsAttribute(): int
    {
        return $this->coins + $this->bonus_coins;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopePopular($query)
    {
        return $query->where('is_popular', 1);
    }
}
