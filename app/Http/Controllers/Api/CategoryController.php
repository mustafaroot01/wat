<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Category;
use App\Http\Resources\CategoryResource;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function index()
    {
        // استخدام الكاش (الذاكرة المؤقتة) للإسراع الفائق في نقل البيانات
        $categories = Cache::rememberForever('api_active_categories', function () {
            return Category::where('is_active', true)
                ->orderBy('sort_order', 'asc')
                ->orderBy('id', 'desc')
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories)
        ]);
    }
}
