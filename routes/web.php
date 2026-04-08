<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

// جسر الصور (Media Proxy) لحل مشكلة 403 مع البادئة /storage/ في السيرفر
Route::get('media/{path}', function ($path) {
    try {
        $basePath = storage_path('app/public/');
        $fullPath = realpath($basePath . $path);
        
        // التحقق من أن الملف موجود وأنه فعلاً داخل مجلد الـ public (حماية من Path Traversal)
        if (!$fullPath || !str_starts_with($fullPath, $basePath) || !File::exists($fullPath)) {
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

Route::get('{any?}', function() {
    return view('application');
})->where('any', '.*');