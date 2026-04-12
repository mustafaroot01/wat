<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku' => 'nullable|string|max:100|unique:products,sku',
            'category_id' => 'required|exists:categories,id',
            'filter_id'   => 'nullable|exists:category_filters,id',
            'brand_id' => 'nullable|exists:brands,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'               => 'required|numeric|min:0',
            'discount_percentage' => 'integer|min:0|max:99',
            'in_stock'            => 'boolean',
            'image'               => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'is_active'           => 'boolean',
            'sort_order'          => 'integer',
        ];
    }
}
