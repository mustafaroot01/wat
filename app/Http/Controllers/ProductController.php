<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Helpers\ImageHelper;
use App\Traits\VuetifyTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    use VuetifyTrait;

    public function index(Request $request)
    {
        $query = Product::with(['category', 'brand', 'filter']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('filter_id')) {
            $query->where('filter_id', $request->filter_id);
        }
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }
        if ($request->filled('in_stock')) {
            $query->where('in_stock', filter_var($request->in_stock, FILTER_VALIDATE_BOOLEAN));
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }
        if ($request->boolean('active_only')) {
            $query->active();
        }

        $products = $this->scopeDataTable(
            $query, $request,
            searchableColumns: ['sku', 'name', 'description'],
            allowedSortColumns: ['name', 'price', 'sort_order', 'created_at']
        );

        return ProductResource::collection($products)
            ->additional(['has_more' => $products->hasMorePages()]);
    }

    public function discounted(Request $request)
    {
        $query = Product::with(['category', 'brand', 'filter'])
            ->discounted()
            ->active();

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }
        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('sku', 'like', "%{$term}%");
            });
        }

        // Sort by highest discount by default
        $sortBy  = $request->get('sort_by', 'discount_percentage');
        $sortDir = $request->get('sort_dir', 'desc');
        $allowed = ['discount_percentage', 'price', 'name', 'created_at'];
        if (!in_array($sortBy, $allowed)) $sortBy = 'discount_percentage';

        $products = $query->orderBy($sortBy, $sortDir)
            ->paginate($request->get('per_page', 15));

        $stats = [
            'total_discounted' => Product::active()->discounted()->count(),
            'avg_discount'     => (int) round(Product::active()->discounted()->avg('discount_percentage') ?? 0),
            'max_discount'     => (int) (Product::active()->discounted()->max('discount_percentage') ?? 0),
        ];

        return ProductResource::collection($products)
            ->additional([
                'has_more' => $products->hasMorePages(),
                'stats'    => $stats,
            ]);
    }

    public function discountedPublic(Request $request)
    {
        $query = Product::with(['category', 'brand', 'filter'])
            ->active()
            ->discounted();

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('sku', 'like', "%{$term}%");
            });
        }

        $sortBy  = $request->get('sort_by', 'discount_percentage');
        $sortDir = $request->get('sort_dir', 'desc');
        if (!in_array($sortBy, ['discount_percentage', 'price', 'name', 'created_at'])) {
            $sortBy = 'discount_percentage';
        }

        $products = $query->orderBy($sortBy, $sortDir)
            ->paginate($request->get('per_page', 20));

        return ProductResource::collection($products)
            ->additional([
                'has_more'        => $products->hasMorePages(),
                'total_discounted' => Product::active()->discounted()->count(),
            ]);
    }

    public function indexPublic(Request $request)
    {
        $query = Product::with(['category', 'brand', 'filter'])->active();

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('filter_id')) {
            $query->where('filter_id', $request->filter_id);
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%");
            });
        }

        $products = $query->ordered()->latest()->paginate($request->get('per_page', 15));

        return ProductResource::collection($products)
            ->additional(['has_more' => $products->hasMorePages()]);
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = ImageHelper::compressAndStore($request->file('image'), 'products');
        }

        $product = Product::create($data);

        return new ProductResource($product->load(['category', 'brand', 'filter']));
    }

    public function show(Product $product)
    {
        return new ProductResource($product->load(['category', 'brand', 'filter']));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = ImageHelper::compressAndStore($request->file('image'), 'products');
        }

        $product->update($data);

        return new ProductResource($product->load(['category', 'brand', 'filter']));
    }

    public function destroy(Product $product)
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        $product->delete();
        return response()->json(['message' => 'Product deleted successfully']);
    }

    public function toggleActive(Product $product)
    {
        $product->update(['is_active' => !$product->is_active]);
        return new ProductResource($product->load(['category', 'brand', 'filter']));
    }

    public function toggleInStock(Product $product)
    {
        $product->update(['in_stock' => !$product->in_stock]);
        return new ProductResource($product->load(['category', 'brand', 'filter']));
    }
}
