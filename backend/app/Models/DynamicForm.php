<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DynamicForm extends Model
{
    protected $fillable = ['act', 'form_data'];

    protected function casts(): array
    {
        return [
            'form_data' => 'array',
        ];
    }

    public function getJsonDataAttribute(): array
    {
        return $this->form_data ?? [];
    }

    public static function getByAct(string $act): ?static
    {
        return static::where('act', $act)->first();
    }
}
