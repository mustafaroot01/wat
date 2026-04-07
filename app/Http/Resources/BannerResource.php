<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'url' => $this->url,
            'category_id' => $this->category_id,
            'product_id' => $this->product_id,
            'duration_type' => $this->duration_type,
            'duration_value' => $this->duration_value,
            'image_url' => $this->image ? asset('storage/' . $this->image) : null,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}
