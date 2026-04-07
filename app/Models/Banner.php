<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'type',
        'url',
        'category_id',
        'product_id',
        'duration_type',
        'duration_value',
        'image',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'category_id' => 'integer',
        'product_id' => 'integer',
        'duration_value' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function product()
    {
        // Add Product model reference here
        return $this->belongsTo(\App\Models\Product::class); // Assuming Product model exists or will exist soon
    }
}
