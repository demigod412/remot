<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPayoutAccount extends Model
{
    protected $fillable = ['user_id', 'payout_method_id', 'label', 'details', 'is_default'];

    protected function casts(): array
    {
        return [
            'details'    => 'array',
            'is_default' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payoutMethod()
    {
        return $this->belongsTo(PayoutMethod::class);
    }

    public function getPrimaryDetailAttribute(): string
    {
        if (empty($this->details)) return '—';
        return (string) collect($this->details)->first();
    }
}
