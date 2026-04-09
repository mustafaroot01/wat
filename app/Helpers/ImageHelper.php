<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class ImageHelper
{
    /**
     * ضغط الصورة وحفظها في disk public
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
        $image = Image::read($file);

        // تصغير إذا تجاوز العرض الأقصى (مع الحفاظ على النسبة)
        if ($image->width() > $maxWidth) {
            $image->scaleDown(width: $maxWidth);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $filename  = Str::uuid() . '.' . $extension;
        $path      = $folder . '/' . $filename;

        $encoded = $image->toWebp($quality);

        // نحفظ كـ webp لتوفير المساحة
        $webpPath = $folder . '/' . Str::uuid() . '.webp';
        Storage::disk('public')->put($webpPath, (string) $encoded);

        return $webpPath;
    }
}
