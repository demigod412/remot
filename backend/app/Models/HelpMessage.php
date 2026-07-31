<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HelpMessage extends Model
{
    protected $fillable = ['help_ticket_id', 'admin_id', 'message'];

    public function ticket()
    {
        return $this->belongsTo(HelpTicket::class, 'help_ticket_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function files()
    {
        return $this->hasMany(HelpFile::class, 'help_message_id');
    }

    public function getIsAdminReplyAttribute(): bool
    {
        return $this->admin_id > 0;
    }
}
