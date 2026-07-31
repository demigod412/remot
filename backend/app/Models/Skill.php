<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Skill extends Model
{
    protected $fillable = ['name', 'slug', 'category_id', 'status'];

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }

    public function category()
    {
        return $this->belongsTo(WorkCategory::class, 'category_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_skills');
    }

    public function works()
    {
        return $this->belongsToMany(Work::class, 'work_skills');
    }

    public function jobListings()
    {
        return $this->belongsToMany(JobListing::class, 'job_listing_skills');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public static function findOrCreateByName(string $name): static
    {
        $slug = Str::slug($name);
        return static::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'slug' => $slug, 'status' => true]
        );
    }
}
