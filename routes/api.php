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

        // Products
        Route::apiResource('products', \App\Http\Controllers\ProductController::class);
        Route::patch('products/{product}/toggle', [\App\Http\Controllers\ProductController::class, 'toggleActive']);

        // Customers
        Route::get('customers', [\App\Http\Controllers\CustomerController::class, 'index']);
        Route::put('customers/{id}', [\App\Http\Controllers\CustomerController::class, 'update']);
        Route::patch('customers/{id}/toggle', [\App\Http\Controllers\CustomerController::class, 'toggleActive']);
        Route::put('customers/{id}/password', [\App\Http\Controllers\CustomerController::class, 'updatePassword']);
        Route::post('customers/{id}/restore', [\App\Http\Controllers\CustomerController::class, 'restore']);

        // General Settings
        Route::get('settings', [\App\Http\Controllers\SettingController::class, 'index']);
        Route::post('settings', [\App\Http\Controllers\SettingController::class, 'store']);
    });
});

// مسارات واجهات الموبايل (Front API)
Route::prefix('app')->group(function () {
    Route::get('categories', [App\Http\Controllers\Api\CategoryController::class, 'index']);
    Route::get('notifications', [\App\Http\Controllers\NotificationController::class, 'index']);
    Route::get('notifications/{notification}', function(\App\Models\AppNotification $notification) {
        return new \App\Http\Resources\NotificationResource($notification);
    });

    // Brands Public API
    Route::get('brands', [\App\Http\Controllers\BrandController::class, 'index']);
    Route::get('brands/{brand}/products', [\App\Http\Controllers\BrandController::class, 'products']);

    // Products Public API
    Route::get('products', [\App\Http\Controllers\ProductController::class, 'index']);

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

    // --- Protected Routes ---
    Route::middleware(['auth:sanctum', 'check.account.active'])->group(function () {
        // Logout
        Route::post('auth/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);

        // Profile
        Route::get('profile', [\App\Http\Controllers\Api\ProfileController::class, 'show']);
        Route::put('profile', [\App\Http\Controllers\Api\ProfileController::class, 'update']);
        Route::put('profile/password', [\App\Http\Controllers\Api\ProfileController::class, 'updatePassword']);
        Route::delete('profile', [\App\Http\Controllers\Api\ProfileController::class, 'deleteAccount']);
    });
});
