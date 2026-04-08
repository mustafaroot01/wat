<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'category_id' => (int) $this->category_id,
            'filter_id'   => $this->filter_id ? (int) $this->filter_id : null,
            'brand_id' => $this->brand_id ? (int) $this->brand_id : null,
            'name' => $this->name,
            'description' => $this->description,
            'price'               => (float) $this->price,
            'discount_percentage' => (int) $this->discount_percentage,
            'discounted_price'    => $this->discount_percentage > 0
                ? round($this->price * (1 - $this->discount_percentage / 100), 2)
                : null,
            'has_discount'        => $this->discount_percentage > 0,
            'in_stock'            => (bool) $this->in_stock,
            'image_url'           => $this->image ? asset('media/' . $this->image) : null,
            'is_active'           => (bool) $this->is_active,
            'sort_order' => (int) $this->sort_order,
            'created_at' => $this->created_at?->toDateTimeString(),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'filter'   => new CategoryFilterResource($this->whenLoaded('filter')),
            'brand' => new BrandResource($this->whenLoaded('brand')),
        ];
    }
}
