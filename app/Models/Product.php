<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'sku',
        'category_id',
        'filter_id',
        'brand_id',
        'name',
        'description',
        'price',
        'discount_percentage',
        'in_stock',
        'image',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active'            => 'boolean',
        'in_stock'             => 'boolean',
        'price'                => 'decimal:2',
        'discount_percentage'  => 'integer',
        'brand_id'             => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->sku)) {
                $product->sku = 'PRD-' . strtoupper(\Illuminate\Support\Str::random(6));
            }
        });
    }

    // --- Scopes ---
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function filter()
    {
        return $this->belongsTo(CategoryFilter::class, 'filter_id');
    }
}
