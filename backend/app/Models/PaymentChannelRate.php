<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentChannelRate extends Model
{
    protected $fillable = [
        'name', 'currency', 'symbol', 'channel_code', 'channel_driver',
        'min_amount', 'max_amount', 'percent_charge', 'fixed_charge',
        'rate', 'image', 'extra_params',
    ];

    protected function casts(): array
    {
        return [
            'min_amount'     => 'decimal:8',
            'max_amount'     => 'decimal:8',
            'percent_charge' => 'decimal:2',
            'fixed_charge'   => 'decimal:8',
            'rate'           => 'decimal:8',
            'extra_params'   => 'array',
        ];
    }

    public function channel()
    {
        return $this->belongsTo(PaymentChannel::class, 'channel_code', 'code');
    }
}
