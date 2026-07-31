<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentSection extends Model
{
    protected $fillable = ['section_key', 'section_data'];

    protected function casts(): array
    {
        return [
            'section_data' => 'array',
        ];
    }

    public function scopeByKey($query, string $key)
    {
        return $query->where('section_key', $key);
    }

    public static function getData(string $key): ?array
    {
        return static::where('section_key', $key)->value('section_data');
    }
}
