<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'slug'       => $this->slug,
            'image_url'  => $this->image ? asset('media/' . ltrim($this->image, '/')) : null,
            'is_active'  => $this->is_active,
            'sort_order' => $this->sort_order,

            // Featured fields
            'is_featured'             => (bool) $this->is_featured,
            'featured_title'          => $this->featured_title,
            'featured_subtitle'       => $this->featured_subtitle,
            'featured_image_url'      => $this->featured_image
                ? asset('media/' . ltrim($this->featured_image, '/'))
                : null,
            'featured_bg_color'       => $this->featured_bg_color ?? '#1565c0',
            'featured_display_style'  => $this->featured_display_style ?? 'full_banner',
        ];
    }
}

