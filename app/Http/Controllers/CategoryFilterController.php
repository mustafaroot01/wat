<?php

namespace App\Http\Controllers;

use App\Models\CategoryFilter;
use App\Http\Resources\CategoryFilterResource;
use Illuminate\Http\Request;

class CategoryFilterController extends Controller
{
    public function index(Request $request)
    {
        $query = CategoryFilter::query();

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $filters = $query->ordered()->get();

        return CategoryFilterResource::collection($filters);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name'        => 'required|string|max:100',
            'sort_order'  => 'nullable|integer|min:0',
            'is_active'   => 'nullable|boolean',
        ]);

        $filter = CategoryFilter::create($data);

        return new CategoryFilterResource($filter);
    }

    public function update(Request $request, CategoryFilter $categoryFilter)
    {
        $data = $request->validate([
            'name'       => 'sometimes|required|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'nullable|boolean',
        ]);

        $categoryFilter->update($data);

        return new CategoryFilterResource($categoryFilter);
    }

    public function destroy(CategoryFilter $categoryFilter)
    {
        $categoryFilter->delete();
        return response()->json(['message' => 'تم الحذف بنجاح.']);
    }

    public function toggleActive(CategoryFilter $categoryFilter)
    {
        $categoryFilter->update(['is_active' => !$categoryFilter->is_active]);
        return new CategoryFilterResource($categoryFilter);
    }
}
