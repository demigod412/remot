<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class JobListing extends Model
{
    protected $table = 'job_listings';

    protected $fillable = [
        'employer_id', 'category_id', 'subcategory_id',
        'title', 'slug', 'description',
        'location', 'location_type', 'employment_type',
        'salary_min', 'salary_max', 'salary_currency', 'salary_visible',
        'requirements', 'benefits', 'cover_image',
        'status', 'rejection_reason', 'application_count', 'closes_at',
        'is_featured', 'featured_until', 'requires_kyc',
    ];

    protected function casts(): array
    {
        return [
            'status'         => 'integer',
            'employer_id'    => 'integer',
            'salary_min'     => 'decimal:2',
            'salary_max'     => 'decimal:2',
            'salary_visible' => 'boolean',
            'closes_at'      => 'datetime',
            'featured_until' => 'datetime',
            'is_featured'    => 'boolean',
            'requires_kyc'   => 'boolean',
        ];
    }

    // ── Relationships ────────────────────────────────────────────

    public function employer()
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    public function category()
    {
        return $this->belongsTo(WorkCategory::class, 'category_id');
    }

    public function boostRequests()
    {
        return $this->morphMany(BoostRequest::class, 'boostable');
    }

    public function subcategory()
    {
        return $this->belongsTo(WorkSubcategory::class, 'subcategory_id');
    }

    public function applications()
    {
        return $this->hasMany(JobApplication::class, 'job_listing_id');
    }

    public function bookmarks()
    {
        return $this->hasMany(JobBookmark::class);
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'job_listing_skills');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)
                     ->where(function ($q) {
                         $q->whereNull('featured_until')
                           ->orWhere('featured_until', '>', now());
                     });
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopePending($query)
    {
        return $query->where('status', 0);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 1)
            ->where(function ($q) {
                $q->whereNull('closes_at')->orWhere('closes_at', '>', now());
            });
    }

    // ── Accessors ────────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return ['Pending', 'Active', 'Closed', 'Rejected'][$this->status] ?? 'Unknown';
    }

    public function getLocationTypeLabelAttribute(): string
    {
        return [1 => 'Remote', 2 => 'On-site', 3 => 'Hybrid'][$this->location_type] ?? 'Remote';
    }

    public function getEmploymentTypeLabelAttribute(): string
    {
        return [
            'full_time'  => 'Full-time',
            'part_time'  => 'Part-time',
            'contract'   => 'Contract',
            'freelance'  => 'Freelance',
        ][$this->employment_type] ?? $this->employment_type;
    }

    public function getSalaryRangeAttribute(): string
    {
        if (! $this->salary_visible || (! $this->salary_min && ! $this->salary_max)) {
            return 'Undisclosed';
        }
        $sym = $this->salary_currency;
        if ($this->salary_min && $this->salary_max) {
            return $sym . ' ' . number_format($this->salary_min) . ' – ' . number_format($this->salary_max);
        }
        return $sym . ' ' . number_format($this->salary_min ?: $this->salary_max);
    }

    public function getIsClosedAttribute(): bool
    {
        return $this->status === 2 || ($this->closes_at && $this->closes_at->isPast());
    }

    // ── Helpers ──────────────────────────────────────────────────

    public static function generateSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base . '-' . Str::random(6);
        return strtolower($slug);
    }
}
