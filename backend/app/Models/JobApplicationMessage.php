<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobApplicationMessage extends Model
{
    protected $table = 'job_application_messages';

    protected $fillable = ['job_application_id', 'sender_id', 'body', 'is_read'];

    protected function casts(): array
    {
        return ['is_read' => 'boolean'];
    }

    public function application()
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
