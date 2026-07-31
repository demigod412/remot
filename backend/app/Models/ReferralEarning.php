<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralEarning extends Model
{
    protected $fillable = [
        'earner_id', 'referred_user_id', 'coins_earned', 'original_amount',
    ];

    protected function casts(): array
    {
        return [
            'coins_earned'    => 'decimal:8',
            'original_amount' => 'decimal:8',
        ];
    }

    public function earner()
    {
        return $this->belongsTo(User::class, 'earner_id');
    }

    public function referredUser()
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }
}
