<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SitePage extends Model
{
    protected $fillable = ['name', 'slug', 'tempname', 'secs', 'is_default'];

    protected function casts(): array
    {
        return [
            'secs'       => 'array',
            'is_default' => 'boolean',
        ];
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', 1);
    }
}
