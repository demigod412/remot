<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSession extends Model
{
    protected $fillable = [
        'user_id', 'user_ip', 'city', 'country', 'country_code',
        'longitude', 'latitude', 'browser', 'os',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
