<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    protected $table = 'job_applications';

    protected $fillable = [
        'job_listing_id', 'applicant_id',
        'cover_letter', 'resume', 'portfolio_url',
        'expected_salary', 'expected_salary_currency',
        'status', 'employer_note', 'is_read', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'status'          => 'integer',
            'job_listing_id'  => 'integer',
            'applicant_id'    => 'integer',
            'expected_salary' => 'decimal:2',
            'is_read'         => 'boolean',
            'reviewed_at'     => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function listing()
    {
        return $this->belongsTo(JobListing::class, 'job_listing_id');
    }

    public function applicant()
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }

    public function messages()
    {
        return $this->hasMany(JobApplicationMessage::class, 'job_application_id');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 0);
    }

    public function scopeShortlisted($query)
    {
        return $query->where('status', 2);
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 3);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    // ── Accessors ────────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return [
            0 => 'Pending',
            1 => 'Reviewed',
            2 => 'Shortlisted',
            3 => 'Accepted',
            4 => 'Rejected',
        ][$this->status] ?? 'Unknown';
    }

    public function getStatusColorAttribute(): string
    {
        return [
            0 => 'yellow',
            1 => 'blue',
            2 => 'purple',
            3 => 'green',
            4 => 'red',
        ][$this->status] ?? 'gray';
    }
}
