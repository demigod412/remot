<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $fillable = ['name', 'code', 'icon', 'text_align', 'is_default'];

    protected function casts(): array
    {
        return [
            'is_default'  => 'boolean',
            'text_align'  => 'boolean',
        ];
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', 1);
    }

    public static function getDefault(): ?static
    {
        return static::where('is_default', 1)->first();
    }
}
