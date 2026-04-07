<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::ordered()->paginate(15);
        return CategoryResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request)
    {
        $data = $request->validated();
        
        // Generate Unique Slug
        $baseSlug = Str::slug($data['name']);
        $slug = $baseSlug;
        $counter = 1;
        while (Category::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        $data['slug'] = $slug;

        $path = null;

        try {
            DB::beginTransaction();

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('categories', 'public');
                $data['image'] = $path;
            }

            $category = Category::create($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => new CategoryResource($category)
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            Log::error('Category store failed: ' . $e->getMessage());
            return response()->json(['message' => 'حدث خطأ أثناء الحفظ'], 500);
        }
    }

    public function show(Category $category)
    {
        return new CategoryResource($category);
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $data = $request->validated();

        if (strcasecmp($category->name, $data['name']) !== 0) {
            $baseSlug = Str::slug($data['name']);
            $slug = $baseSlug;
            $counter = 1;
            while (Category::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            $data['slug'] = $slug;
        }

        $oldImage = $category->image;
        $newPath = null;

        try {
            DB::beginTransaction();

            if ($request->hasFile('image')) {
                $newPath = $request->file('image')->store('categories', 'public');
                $data['image'] = $newPath;
            }

            $category->update($data);

            if ($request->hasFile('image') && $oldImage) {
                Storage::disk('public')->delete($oldImage);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => new CategoryResource($category)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }
            Log::error('Category update failed: ' . $e->getMessage());
            return response()->json(['message' => 'حدث خطأ أثناء التعديل'], 500);
        }
    }

    public function destroy(Category $category)
    {
        // TODO: Add check here to prevent deletion if there are products linked
        // if ($category->products()->count() > 0) {
        //     return response()->json(['message' => 'لا يمكن حذف القسم لأنه مرتبط بمنتجات'], 403);
        // }

        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }
        $category->delete();

        return response()->json(['success' => true, 'message' => 'Category deleted successfully']);
    }

    public function toggleActive(Category $category)
    {
        $category->update(['is_active' => !$category->is_active]);
        
        return response()->json(['success' => true, 'is_active' => $category->is_active]);
    }
}
