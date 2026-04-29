<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageHelper
{
    /**
     * ضغط الصورة وحفظها في disk public باستخدام PHP GD المدمج
     *
     * @param  UploadedFile  $file
     * @param  string        $folder   مثال: 'products', 'banners'
     * @param  int           $quality  جودة الضغط 1-100 (افتراضي 80)
     * @param  int           $maxWidth الحد الأقصى للعرض بالبكسل (افتراضي 1200)
     * @return string  المسار المحفوظ نسبةً لـ storage/app/public
     */
    public static function compressAndStore(
        UploadedFile $file,
        string $folder,
        int $quality = 80,
        int $maxWidth = 1200
    ): string {
        $mimeType = $file->getMimeType();
        $realPath = $file->getRealPath();

        // SVG: حفظ مباشر بدون معالجة
        if ($mimeType === 'image/svg+xml') {
            $ext  = 'svg';
            $path = $folder . '/' . Str::uuid() . '.' . $ext;
            Storage::disk('public')->put($path, file_get_contents($realPath));
            return $path;
        }

        // رفع حد الذاكرة مؤقتاً لمعالجة الصور الكبيرة
        $prevMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '512M');

        // إنشاء GD resource مباشرة من الملف بدون تحميله كاملاً بالذاكرة
        $src = match($mimeType) {
            'image/jpeg' => @imagecreatefromjpeg($realPath),
            'image/png'  => @imagecreatefrompng($realPath),
            'image/gif'  => @imagecreatefromgif($realPath),
            'image/webp' => @imagecreatefromwebp($realPath),
            default      => @imagecreatefromstring(file_get_contents($realPath)),
        };

        // إذا فشل GD نحفظ الملف الأصلي مباشرةً
        if (!$src) {
            ini_set('memory_limit', $prevMemoryLimit);
            $ext  = strtolower($file->getClientOriginalExtension()) ?: 'jpg';
            $path = $folder . '/' . Str::uuid() . '.' . $ext;
            Storage::disk('public')->put($path, file_get_contents($realPath));
            return $path;
        }

        $origW = imagesx($src);
        $origH = imagesy($src);

        // تصغير إذا تجاوز العرض الأقصى مع الحفاظ على النسبة
        if ($origW > $maxWidth) {
            $newW = $maxWidth;
            $newH = (int) round($origH * ($maxWidth / $origW));
            $dst  = imagecreatetruecolor($newW, $newH);

            // دعم الشفافية للـ PNG
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
            imagedestroy($src);
            $src = $dst;
        }

        $uuid = Str::uuid();
        $data = null;

        // حفظ كـ WebP إذا كان مدعوماً
        if (function_exists('imagewebp')) {
            ob_start();
            @imagewebp($src, null, $quality);
            $data = ob_get_clean();
        }

        // إذا WebP فشل أو أنتج ملف فاضي → fallback لـ JPEG
        if (!$data || strlen($data) < 100) {
            ob_start();
            @imagejpeg($src, null, $quality);
            $data = ob_get_clean();
            $path = $folder . '/' . $uuid . '.jpg';
        } else {
            $path = $folder . '/' . $uuid . '.webp';
        }

        imagedestroy($src);

        // إذا كلاهما فشل → حفظ الملف الأصلي
        if (!$data || strlen($data) < 100) {
            $ext  = strtolower($file->getClientOriginalExtension()) ?: 'jpg';
            $path = $folder . '/' . $uuid . '.' . $ext;
            Storage::disk('public')->put($path, file_get_contents($realPath));
            return $path;
        }

        Storage::disk('public')->put($path, $data);

        ini_set('memory_limit', $prevMemoryLimit);

        return $path;
    }
}
