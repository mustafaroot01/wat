<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'image',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderByDesc('id');
    }

    public function products()
    {
        // Add Product model reference here
        return $this->hasMany(\App\Models\Product::class);
    }

    protected static function booted()
    {
        static::saved(function () {
            Cache::forget('api_active_categories');
        });

        static::deleting(function ($category) {
            Cache::forget('api_active_categories');
        });
    }
}
