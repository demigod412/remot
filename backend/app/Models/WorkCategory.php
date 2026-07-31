<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkCategory extends Model
{
    protected $fillable = ['name', 'icon', 'status'];

    public function subcategories()
    {
        return $this->hasMany(WorkSubcategory::class, 'category_id');
    }

    public function works()
    {
        return $this->hasMany(Work::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
