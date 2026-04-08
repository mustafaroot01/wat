<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $query = Brand::query();

        if ($request->boolean('active_only')) {
            $query->active();
        }

        $perPage = min((int) $request->get('per_page', 15), 100);

        $brands = $query
            ->withCount('products')
            ->ordered()
            ->paginate($perPage);

        return BrandResource::collection($brands)
            ->additional(['has_more' => $brands->hasMorePages()]);
    }

    public function indexPublic(Request $request)
    {
        $perPage = min((int) $request->get('per_page', 15), 100);

        $brands = Brand::active()
            ->withCount('products')
            ->ordered()
            ->paginate($perPage);

        return BrandResource::collection($brands)
            ->additional(['has_more' => $brands->hasMorePages()]);
    }

    public function store(StoreBrandRequest $request)
    {
        $data = $request->validated();

        $baseSlug = Str::slug($data['name']);
        $slug = $baseSlug;
        $counter = 1;
        while (Brand::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }
        $data['slug'] = $slug;

        $path = null;

        try {
            DB::beginTransaction();

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('brands', 'public');
                $data['image'] = $path;
            }

            $brand = Brand::create($data);

            DB::commit();

            return new BrandResource($brand);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            return response()->json(['message' => 'فشل إضافة العلامة التجارية.'], 500);
        }
    }

    public function show(Brand $brand)
    {
        return new BrandResource($brand->loadCount('products'));
    }

    public function products(Request $request, Brand $brand)
    {
        $perPage = min((int) $request->get('per_page', 15), 100);

        $products = $brand->products()
            ->with(['category', 'filter'])
            ->where('is_active', true)
            ->ordered()
            ->paginate($perPage);

        return ProductResource::collection($products)
            ->additional(['has_more' => $products->hasMorePages()]);
    }

    public function update(UpdateBrandRequest $request, Brand $brand)
    {
        $data = $request->validated();
        
        if (isset($data['name'])) {
            $baseSlug = Str::slug($data['name']);
            $slug = $baseSlug;
            $counter = 1;
            while (Brand::where('slug', $slug)->where('id', '!=', $brand->id)->exists()) {
                $slug = $baseSlug . '-' . $counter++;
            }
            $data['slug'] = $slug;
        }

        if ($request->hasFile('image')) {
            if ($brand->image) {
                Storage::disk('public')->delete($brand->image);
            }
            $data['image'] = $request->file('image')->store('brands', 'public');
        }

        $brand->update($data);

        return new BrandResource($brand);
    }

    public function destroy(Brand $brand)
    {
        if ($brand->products()->exists()) {
            return response()->json([
                'message' => 'Cannot delete brand with associated products.'
            ], 422);
        }

        if ($brand->image) {
            Storage::disk('public')->delete($brand->image);
        }

        $brand->delete();

        return response()->json(['message' => 'Brand deleted successfully']);
    }

    public function toggleActive(Brand $brand)
    {
        $brand->update(['is_active' => !$brand->is_active]);
        $brand->refresh();
        return new BrandResource($brand);
    }
}
