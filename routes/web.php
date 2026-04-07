<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

// جسر الصور (Media Proxy) لحل مشكلة 403 مع البادئة /storage/ في السيرفر
Route::get('media/{path}', function ($path) {
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

Route::get('{any?}', function() {
    return view('application');
})->where('any', '.*');