<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plugin extends Model
{
    protected $fillable = [
        'act', 'name', 'description', 'image',
        'script', 'shortcode', 'support', 'status', 'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'shortcode'  => 'array',
            'deleted_at' => 'datetime',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public static function getByAct(string $act): ?static
    {
        return static::where('act', $act)->where('status', 1)->first();
    }
}
