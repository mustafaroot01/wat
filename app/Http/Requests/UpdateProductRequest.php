<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku' => 'nullable|string|max:100|unique:products,sku,' . $this->route('product')->id,
            'category_id' => 'sometimes|required|exists:categories,id',
            'filter_id'   => 'nullable|exists:category_filters,id',
            'brand_id' => 'sometimes|nullable|exists:brands,id',
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price'               => 'sometimes|required|numeric|min:0',
            'discount_percentage' => 'sometimes|integer|min:0|max:99',
            'in_stock'            => 'sometimes|boolean',
            'image'               => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active'           => 'boolean',
            'sort_order'          => 'integer',
        ];
    }
}
