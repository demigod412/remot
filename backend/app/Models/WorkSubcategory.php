<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkSubcategory extends Model
{
    protected $fillable = ['category_id', 'name', 'status'];

    public function category()
    {
        return $this->belongsTo(WorkCategory::class, 'category_id');
    }

    public function works()
    {
        return $this->hasMany(Work::class, 'subcategory_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
