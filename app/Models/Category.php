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
        'is_featured',
        'featured_title',
        'featured_subtitle',
        'featured_image',
        'featured_bg_color',
        'featured_display_style',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'is_featured' => 'boolean',
        'sort_order'  => 'integer',
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
        return $this->hasMany(\App\Models\Product::class);
    }

    protected static function booted()
    {
        static::saved(function () {
            Cache::forget('api_active_categories');
            Cache::forget('api_featured_category');
        });

        static::deleting(function ($category) {
            Cache::forget('api_active_categories');
            Cache::forget('api_featured_category');
        });
    }
}

