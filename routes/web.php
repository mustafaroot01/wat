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

// صفحة سياسة الخصوصية — رابط عام مطلوب لمتجر أبل وأندرويد
Route::get('privacy-policy', function () {
    $content   = \App\Models\StoreSetting::get('privacy_policy', '<p>لا توجد سياسة خصوصية محددة حتى الآن.</p>');
    $storeName = \App\Models\StoreSetting::get('store_name', 'امواج ديالى');
    $logoPath  = \App\Models\StoreSetting::get('logo');
    $logoUrl   = $logoPath ? url('storage/' . $logoPath) : null;
    $html = <<<HTML
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>سياسة الخصوصية — {$storeName}</title>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:system-ui,-apple-system,sans-serif;background:#f8f9fa;color:#1a1a2e;direction:rtl}
    .header{background:linear-gradient(135deg,#1565c0,#0d47a1);color:#fff;padding:2rem 1rem;text-align:center}
    .header img{max-height:60px;margin-bottom:1rem;border-radius:8px}
    .header h1{font-size:1.6rem;font-weight:700;margin-bottom:.25rem}
    .header p{opacity:.8;font-size:.9rem}
    .container{max-width:800px;margin:2rem auto;padding:0 1rem}
    .card{background:#fff;border-radius:12px;padding:2rem;box-shadow:0 2px 12px rgba(0,0,0,.08);line-height:1.9}
    .card h2{color:#1565c0;font-size:1.2rem;margin:1.5rem 0 .5rem;padding-bottom:.25rem;border-bottom:2px solid #e3f2fd}
    .card h3{color:#333;margin:1rem 0 .25rem}
    .card p{margin-bottom:.75rem;color:#444}
    .card ul{padding-right:1.5rem;margin-bottom:.75rem;color:#444}
    .card ul li{margin-bottom:.3rem}
    .footer{text-align:center;padding:2rem 1rem;color:#888;font-size:.85rem}
  </style>
</head>
<body>
  <div class="header">
    {$logoHtml}
    <h1>سياسة الخصوصية</h1>
    <p>{$storeName}</p>
  </div>
  <div class="container">
    <div class="card">{$content}</div>
  </div>
  <div class="footer">© {$storeName} — جميع الحقوق محفوظة</div>
</body>
</html>
HTML;
    $logoHtml = $logoUrl ? "<img src=\"{$logoUrl}\" alt=\"{$storeName}\">" : "<h2 style='font-size:1.8rem'>{$storeName}</h2>";
    $html = str_replace('{$logoHtml}', $logoHtml, $html);
    return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
});

Route::get('{any?}', function() {
    return view('application');
})->where('any', '.*');
