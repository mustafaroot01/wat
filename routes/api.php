<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// مسارات لوحة التحكم للإدارة
Route::prefix('admin')->group(function () {
    // Admin Login
    Route::post('login', [\App\Http\Controllers\Api\AdminAuthController::class, 'login']);

    // محمية بصلاحيات الدخول
    Route::middleware(['auth:sanctum', 'admin.role'])->group(function () {
        Route::apiResource('categories', \App\Http\Controllers\CategoryController::class);
        Route::patch('categories/{category}/toggle', [\App\Http\Controllers\CategoryController::class, 'toggleActive']);
        Route::patch('categories/{category}/toggle-featured', [\App\Http\Controllers\CategoryController::class, 'toggleFeatured']);
        Route::post('categories/{category}/featured-settings', [\App\Http\Controllers\CategoryController::class, 'updateFeaturedSettings']);
        
        // Banners
        Route::apiResource('banners', \App\Http\Controllers\BannerController::class);
        Route::patch('banners/{banner}/toggle', [\App\Http\Controllers\BannerController::class, 'toggle']);
        // Notifications
        Route::apiResource('notifications', \App\Http\Controllers\NotificationController::class)->only(['index', 'store', 'destroy']);
        
        // Firebase Settings
        Route::get('firebase-settings', [\App\Http\Controllers\FirebaseSettingsController::class, 'index']);
        Route::post('firebase-settings/save', [\App\Http\Controllers\FirebaseSettingsController::class, 'storeSettings']);
        Route::post('firebase-settings/upload', [\App\Http\Controllers\FirebaseSettingsController::class, 'uploadJson']);
        Route::post('firebase-settings/test', [\App\Http\Controllers\FirebaseSettingsController::class, 'sendTest']);

        // Districts & Areas
        Route::apiResource('districts', \App\Http\Controllers\DistrictController::class);
        Route::patch('districts/{district}/toggle', [\App\Http\Controllers\DistrictController::class, 'toggleActive']);
        Route::apiResource('areas', \App\Http\Controllers\AreaController::class);
        Route::patch('areas/{area}/toggle', [\App\Http\Controllers\AreaController::class, 'toggleActive']);

        // Brands
        Route::apiResource('brands', \App\Http\Controllers\BrandController::class);
        Route::patch('brands/{brand}/toggle', [\App\Http\Controllers\BrandController::class, 'toggleActive']);

        // Category Filters
        Route::get('category-filters', [\App\Http\Controllers\CategoryFilterController::class, 'index']);
        Route::post('category-filters', [\App\Http\Controllers\CategoryFilterController::class, 'store']);
        Route::put('category-filters/{categoryFilter}', [\App\Http\Controllers\CategoryFilterController::class, 'update']);
        Route::delete('category-filters/{categoryFilter}', [\App\Http\Controllers\CategoryFilterController::class, 'destroy']);
        Route::patch('category-filters/{categoryFilter}/toggle', [\App\Http\Controllers\CategoryFilterController::class, 'toggleActive']);

        // Products
        Route::get('products/discounted', [\App\Http\Controllers\ProductController::class, 'discounted']);
        Route::apiResource('products', \App\Http\Controllers\ProductController::class);
        Route::patch('products/{product}/toggle', [\App\Http\Controllers\ProductController::class, 'toggleActive']);
        Route::patch('products/{product}/toggle-stock', [\App\Http\Controllers\ProductController::class, 'toggleInStock']);

        // Customers
        Route::get('customers', [\App\Http\Controllers\CustomerController::class, 'index']);
        Route::get('customers/{id}/orders', [\App\Http\Controllers\CustomerController::class, 'orders']);
        Route::put('customers/{id}', [\App\Http\Controllers\CustomerController::class, 'update']);
        Route::patch('customers/{id}/toggle', [\App\Http\Controllers\CustomerController::class, 'toggleActive']);
        Route::put('customers/{id}/password', [\App\Http\Controllers\CustomerController::class, 'updatePassword']);
        Route::post('customers/{id}/restore', [\App\Http\Controllers\CustomerController::class, 'restore']);
        // Coupons
        Route::get('coupons', [\App\Http\Controllers\CouponController::class, 'index']);
        Route::post('coupons', [\App\Http\Controllers\CouponController::class, 'store']);
        Route::get('coupons/{coupon}', [\App\Http\Controllers\CouponController::class, 'show']);
        Route::get('coupons/{coupon}/usages', [\App\Http\Controllers\CouponController::class, 'usages']);
        Route::get('coupons/{coupon}/export', [\App\Http\Controllers\CouponController::class, 'exportExcel']);
        Route::put('coupons/{coupon}', [\App\Http\Controllers\CouponController::class, 'update']);
        Route::delete('coupons/{coupon}', [\App\Http\Controllers\CouponController::class, 'destroy']);
        Route::patch('coupons/{coupon}/toggle', [\App\Http\Controllers\CouponController::class, 'toggleActive']);

        // Dashboard
        Route::get('dashboard', [\App\Http\Controllers\DashboardController::class, 'index']);

        // General Settings
        Route::get('settings', [\App\Http\Controllers\SettingController::class, 'index']);
        Route::post('settings', [\App\Http\Controllers\SettingController::class, 'store']);

        // Orders (admin)
        Route::get('orders', [\App\Http\Controllers\OrderController::class, 'index']);
        Route::get('orders/bulk-invoice', [\App\Http\Controllers\OrderController::class, 'bulkInvoice']);
        Route::get('orders/{id}', [\App\Http\Controllers\OrderController::class, 'show']);
        Route::patch('orders/{id}/status', [\App\Http\Controllers\OrderController::class, 'updateStatus']);
        Route::delete('orders/{id}', [\App\Http\Controllers\OrderController::class, 'destroy']);
        Route::patch('invoice/{token}/status', [\App\Http\Controllers\OrderController::class, 'updateStatusByToken']);

        // Store Settings
        Route::get('store-settings', [\App\Http\Controllers\StoreSettingsController::class, 'index']);
        Route::post('store-settings', [\App\Http\Controllers\StoreSettingsController::class, 'update']);

        // Current admin info + permissions
        Route::get('me', [\App\Http\Controllers\AdminController::class, 'me']);

        // Admins Management (super admin only — enforced in controller)
        Route::get('admins', [\App\Http\Controllers\AdminController::class, 'index']);
        Route::post('admins', [\App\Http\Controllers\AdminController::class, 'store']);
        Route::put('admins/{admin}', [\App\Http\Controllers\AdminController::class, 'update']);
        Route::delete('admins/{admin}', [\App\Http\Controllers\AdminController::class, 'destroy']);
        Route::patch('admins/{admin}/toggle', [\App\Http\Controllers\AdminController::class, 'toggleActive']);
    });
});

// Public invoice by QR token (no auth required for viewing)
Route::get('invoice/{token}', [\App\Http\Controllers\OrderController::class, 'invoiceByToken']);

// مسارات واجهات الموبايل (Front API)
Route::prefix('app')->group(function () {
    Route::get('categories', [App\Http\Controllers\Api\CategoryController::class, 'index']);
    Route::get('notifications', [App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::get('notifications/{notification}', [App\Http\Controllers\Api\NotificationController::class, 'show']);

    // Banners Public API
    Route::get('banners', [\App\Http\Controllers\BannerController::class, 'indexPublic']);

    // Featured Category Public API
    Route::get('categories/featured', [\App\Http\Controllers\CategoryController::class, 'featured']);

    // Brands Public API
    Route::get('brands', [\App\Http\Controllers\BrandController::class, 'indexPublic']);
    Route::get('brands/{brand}/products', [\App\Http\Controllers\BrandController::class, 'products']);

    // Category Filters Public API
    Route::get('categories/{category}/filters', function (\App\Models\Category $category) {
        $filters = \App\Models\CategoryFilter::where('category_id', $category->id)
            ->active()->ordered()->get();
        return \App\Http\Resources\CategoryFilterResource::collection($filters);
    });

    // Products Public API
    Route::get('products/search', [\App\Http\Controllers\ProductController::class, 'searchPublic']);
    Route::get('products/discounted', [\App\Http\Controllers\ProductController::class, 'discountedPublic']);
    Route::get('products', [\App\Http\Controllers\ProductController::class, 'indexPublic']);

    // Store Status
    Route::get('store-status', function () {
        $isOpen = \App\Models\StoreSetting::isOpenNow();
        return response()->json([
            'is_open' => $isOpen,
            'message' => $isOpen ? 'المتجر مفتوح' : 'المتجر مغلق حالياً، يُرجى المحاولة خلال أوقات الدوام.',
        ]);
    });

    // Store Branding Public API (no auth - used by login page & sidebar)
    Route::get('branding', function () {
        $settings = \App\Models\StoreSetting::allAsArray();
        return response()->json([
            'store_name' => $settings['store_name'] ?? 'امواج ديالى',
            'logo_url'   => isset($settings['logo']) ? url('storage/' . $settings['logo']) : null,
        ]);
    });

    // --- Authentication ---
    Route::prefix('auth')->group(function () {
        // Login
        Route::middleware('throttle:login')->post('login', [\App\Http\Controllers\Api\AuthController::class, 'login']);

        // Register
        Route::middleware('throttle:otp-send')->post('register/send-otp', [\App\Http\Controllers\Api\AuthController::class, 'registerSendOtp']);
        Route::middleware('throttle:otp-verify')->post('register/verify', [\App\Http\Controllers\Api\AuthController::class, 'registerVerify']);

        // Forgot Password
        Route::middleware('throttle:otp-send')->post('forgot-password/send-otp', [\App\Http\Controllers\Api\AuthController::class, 'forgotPasswordSendOtp']);
        Route::middleware('throttle:otp-verify')->post('forgot-password/verify', [\App\Http\Controllers\Api\AuthController::class, 'forgotPasswordVerifyAndReset']);
    });

    // Districts & Areas Public API (for registration form)
    Route::get('districts', function () {
        $districts = \App\Models\District::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        return response()->json($districts);
    });
    Route::get('districts/{district}/areas', function (\App\Models\District $district) {
        $areas = \App\Models\Area::where('district_id', $district->id)
            ->where('is_active', true)->orderBy('name')->get(['id', 'name', 'district_id']);
        return response()->json($areas);
    });

    // --- Protected Routes ---
    Route::middleware(['auth:sanctum', 'check.account.active'])->group(function () {
        // Logout
        Route::post('auth/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);

        // Coupons
        Route::post('coupons/validate', [\App\Http\Controllers\CouponController::class, 'validate']);
        Route::post('coupons/apply', [\App\Http\Controllers\CouponController::class, 'apply']);

        // Orders (app)
        Route::get('orders', [\App\Http\Controllers\OrderController::class, 'myOrders']);
        Route::get('orders/{id}', [\App\Http\Controllers\OrderController::class, 'myOrderShow']);
        Route::post('orders', [\App\Http\Controllers\OrderController::class, 'store']);
        Route::patch('orders/{id}/cancel', [\App\Http\Controllers\OrderController::class, 'cancel']);

        // Profile
        Route::get('profile', [\App\Http\Controllers\Api\ProfileController::class, 'show']);
        Route::put('profile', [\App\Http\Controllers\Api\ProfileController::class, 'update']);
        Route::put('profile/password', [\App\Http\Controllers\Api\ProfileController::class, 'updatePassword']);
        Route::delete('profile', [\App\Http\Controllers\Api\ProfileController::class, 'deleteAccount']);

        // FCM Token
        Route::post('fcm-token', function (Request $request) {
            $request->validate(['token' => 'required|string']);
            $request->user()->update(['fcm_token' => $request->token]);
            return response()->json(['success' => true]);
        });
    });
});
