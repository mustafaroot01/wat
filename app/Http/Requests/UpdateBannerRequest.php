<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'sometimes|string|in:none,link,category,product',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'url' => 'nullable|url|required_if:type,link',
            'category_id' => 'nullable|integer|exists:categories,id|required_if:type,category',
            'product_id' => 'nullable|integer|required_if:type,product',
            'duration_type' => 'nullable|string|in:hours,days',
            'duration_value' => 'nullable|integer|min:1',
            'sort_order' => 'nullable|integer',
        ];
    }
}
