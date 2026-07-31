<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HelpFile extends Model
{
    protected $fillable = ['help_message_id', 'attachment'];

    public function message()
    {
        return $this->belongsTo(HelpMessage::class, 'help_message_id');
    }

    public function getUrlAttribute(): string
    {
        // Private file — served only through the access-controlled secure route.
        return route('secure.helpFile', $this->id);
    }

    public function getIsImageAttribute(): bool
    {
        $ext = strtolower(pathinfo($this->attachment ?? '', PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    }
}
