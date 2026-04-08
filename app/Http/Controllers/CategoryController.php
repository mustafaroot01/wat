<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Traits\VuetifyTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    use VuetifyTrait;

    public function index(Request $request)
    {
        $query = Category::query();

        $categories = $this->scopeDataTable(
            $query, $request,
            searchableColumns: ['name', 'slug'],
            allowedSortColumns: ['name', 'slug', 'sort_order']
        );

        return CategoryResource::collection($categories)
            ->additional(['has_more' => $categories->hasMorePages()]);
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
        if ($category->products()->count() > 0) {
            return response()->json(['message' => 'لا يمكن حذف القسم لأنه مرتبط بمنتجات'], 403);
        }

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

    /**
     * تعيين قسم كـ مميز (يُلغي التمييز عن القسم السابق تلقائياً)
     */
    public function toggleFeatured(Category $category)
    {
        if ($category->is_featured) {
            // إلغاء التمييز إذا كان مميزاً بالفعل
            $category->update(['is_featured' => false]);
        } else {
            // إلغاء التمييز عن القسم القديم ثم تعيين الجديد
            Category::where('is_featured', true)->update(['is_featured' => false]);
            $category->update(['is_featured' => true]);
        }

        return response()->json([
            'success'     => true,
            'is_featured' => $category->is_featured,
            'data'        => new CategoryResource($category),
        ]);
    }

    /**
     * تحديث إعدادات العرض المميز (عنوان، صورة، لون، أسلوب)
     */
    public function updateFeaturedSettings(Request $request, Category $category)
    {
        $data = $request->validate([
            'featured_title'         => 'nullable|string|max:100',
            'featured_subtitle'      => 'nullable|string|max:200',
            'featured_bg_color'      => 'nullable|string|max:20',
            'featured_display_style' => 'nullable|in:full_banner,card,circle',
        ]);

        $oldImage = $category->featured_image;

        if ($request->hasFile('featured_image')) {
            $path = $request->file('featured_image')->store('categories/featured', 'public');
            $data['featured_image'] = $path;
            if ($oldImage) \Illuminate\Support\Facades\Storage::disk('public')->delete($oldImage);
        }

        $category->update($data);

        return response()->json([
            'success' => true,
            'data'    => new CategoryResource($category),
        ]);
    }

    /**
     * API للتطبيق: يرجع القسم المميز فقط
     */
    public function featured()
    {
        $category = \Illuminate\Support\Facades\Cache::remember('api_featured_category', 3600, function () {
            return Category::active()->where('is_featured', true)->first();
        });

        if (!$category) {
            return response()->json(['data' => null]);
        }

        return response()->json(['data' => new CategoryResource($category)]);
    }
}

