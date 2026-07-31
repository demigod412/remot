<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayoutMethod extends Model
{
    protected $fillable = [
        'form_id', 'name', 'driver', 'credentials', 'min_coins', 'max_coins',
        'fixed_fee', 'coin_to_currency_rate', 'percent_fee', 'currency', 'description', 'status',
    ];

    protected function casts(): array
    {
        return [
            'min_coins'             => 'decimal:8',
            'max_coins'             => 'decimal:8',
            'fixed_fee'             => 'decimal:8',
            'coin_to_currency_rate' => 'decimal:8',
            'percent_fee'           => 'decimal:2',
            'credentials'           => 'array',
        ];
    }

    public function cashouts()
    {
        return $this->hasMany(Cashout::class);
    }

    public function form()
    {
        return $this->belongsTo(DynamicForm::class, 'form_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function calculatePayout(float $coinAmount): array
    {
        $percentFee = ($coinAmount * (float) $this->percent_fee) / 100;
        $totalFee   = $percentFee + (float) $this->fixed_fee;
        // Fee is deducted from the payout value (not added to the coins taken)
        $payout     = max(0, ($coinAmount - $totalFee) * (float) $this->coin_to_currency_rate);

        return [
            'coin_amount'        => $coinAmount,
            'fee'                => round($totalFee, 8),
            'net_coins_deducted' => $coinAmount,    // user loses exactly what they entered
            'payout_amount'      => round($payout, 2),
        ];
    }
}
