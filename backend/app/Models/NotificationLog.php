<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    protected $fillable = [
        'user_id', 'sender', 'sent_from', 'sent_to',
        'subject', 'message', 'notification_type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeEmail($query)
    {
        return $query->where('notification_type', 'email');
    }

    public function scopeSms($query)
    {
        return $query->where('notification_type', 'sms');
    }
}
