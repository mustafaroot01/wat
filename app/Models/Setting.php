<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Get a setting value by key with persistent caching
     */
    public static function get(string $key, $default = null)
    {
        $value = Cache::rememberForever('setting_' . $key, function () use ($key) {
            return self::where('key', $key)->first()?->value;
        });

        return $value ?? $default;
    }

    /**
     * Set a setting value and clear cache
     */
    public static function set(string $key, $value)
    {
        $setting = self::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('setting_' . $key);
        return $setting;
    }
}
