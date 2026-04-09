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
        $contents = file_get_contents($file->getRealPath());

        // إنشاء GD resource من محتوى الملف
        $src = @imagecreatefromstring($contents);

        // إذا فشل GD أو الملف SVG نحفظه مباشرةً
        if (!$src || $mimeType === 'image/svg+xml') {
            $ext  = strtolower($file->getClientOriginalExtension()) ?: 'png';
            $path = $folder . '/' . Str::uuid() . '.' . $ext;
            Storage::disk('public')->put($path, $contents);
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

        // حفظ كـ WebP إذا كان مدعوماً
        if (function_exists('imagewebp')) {
            $path = $folder . '/' . Str::uuid() . '.webp';
            ob_start();
            imagewebp($src, null, $quality);
            $data = ob_get_clean();
        } else {
            // fallback: JPEG
            $path = $folder . '/' . Str::uuid() . '.jpg';
            ob_start();
            imagejpeg($src, null, $quality);
            $data = ob_get_clean();
        }

        imagedestroy($src);
        Storage::disk('public')->put($path, $data);

        return $path;
    }
}
