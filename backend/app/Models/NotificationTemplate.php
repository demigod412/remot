<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'act', 'name', 'subj',
        'email_body', 'sms_body', 'shortcodes',
        'email_status', 'sms_status',
    ];

    protected function casts(): array
    {
        return [
            'email_status' => 'boolean',
            'sms_status'   => 'boolean',
        ];
    }

    public static function getByAct(string $act): ?static
    {
        return static::where('act', $act)->first();
    }
}
