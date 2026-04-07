<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

// جسر الصور (Storage Proxy) لحل مشكلة عدم عمل الروابط التلقائية في السيرفر
Route::get('storage/{path}', function ($path) {
    try {
        $fullPath = storage_path('app/public/' . $path);
        
        if (!File::exists($fullPath)) {
            abort(404);
        }

        $file = File::get($fullPath);
        $type = File::mimeType($fullPath);

        $response = Response::make($file, 200);
        $response->header("Content-Type", $type);
        $response->header("Cache-Control", "public, max-age=86400"); // تخزين في الكاش ليوم واحد للأداء

        return $response;
    } catch (\Exception $e) {
        abort(404);
    }
})->where('path', '.*');

// رابط اختبار للتأكد من أن الكود الجديد وصل للسيرفر
Route::get('api-test', function() {
    return response()->json([
        'status' => 'online',
        'time' => now()->toDateTimeString(),
        'storage_path_exists' => File::exists(storage_path('app/public')),
    ]);
});

Route::get('{any?}', function() {
    return view('application');
})->where('any', '.*');