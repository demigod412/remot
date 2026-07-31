<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HelpTicket extends Model
{
    protected $fillable = [
        'user_id', 'name', 'email', 'ticket_number',
        'subject', 'status', 'priority', 'last_reply_at',
    ];

    protected function casts(): array
    {
        return [
            'last_reply_at' => 'datetime',
            'status'        => 'integer',
            'priority'      => 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(HelpMessage::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeOpen($query)
    {
        return $query->where('status', 0);
    }

    public function scopeAnswered($query)
    {
        return $query->where('status', 1);
    }

    public function scopeReplied($query)
    {
        return $query->where('status', 2);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 3);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            0 => 'Open',
            1 => 'Answered',
            2 => 'Customer Reply',
            3 => 'Closed',
            default => 'Unknown',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            0 => 'blue',
            1 => 'green',
            2 => 'yellow',
            3 => 'gray',
            default => 'gray',
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            1 => 'Low',
            2 => 'Medium',
            3 => 'High',
            default => 'Normal',
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            1 => 'gray',
            2 => 'yellow',
            3 => 'red',
            default => 'gray',
        };
    }
}
