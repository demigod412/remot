<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkBookmark extends Model
{
    protected $fillable = ['user_id', 'work_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function work()
    {
        return $this->belongsTo(Work::class);
    }
}
