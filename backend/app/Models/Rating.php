<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $fillable = ['rater_id', 'ratee_id', 'ratable_id', 'ratable_type', 'rating', 'review'];

    public function rater()
    {
        return $this->belongsTo(User::class, 'rater_id');
    }

    public function ratee()
    {
        return $this->belongsTo(User::class, 'ratee_id');
    }

    public function ratable()
    {
        return $this->morphTo();
    }

    // True when the counterpart has also submitted a rating (blind reveal logic)
    public function isRevealed(): bool
    {
        return static::where('rater_id', $this->ratee_id)
            ->where('ratee_id', $this->rater_id)
            ->where('ratable_id', $this->ratable_id)
            ->where('ratable_type', $this->ratable_type)
            ->exists();
    }

    public function getStarsHtmlAttribute(): string
    {
        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            $stars .= $i <= $this->rating ? '★' : '☆';
        }
        return $stars;
    }
}
