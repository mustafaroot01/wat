<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class StoreSetting extends Model
{
    protected $table = 'store_settings';

    protected $fillable = ['key', 'value'];

    public static function get(string $key, $default = null)
    {
        $value = Cache::remember('store_setting_' . $key, 3600, function () use ($key) {
            return static::where('key', $key)->first()?->value;
        });

        return $value ?? $default;
    }

    public static function set(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('store_setting_' . $key);
        Cache::forget('store_settings_all');
    }

    public static function allAsArray(): array
    {
        return Cache::remember('store_settings_all', 3600, function () {
            return static::all()->pluck('value', 'key')->toArray();
        });
    }

    public static function isOpenNow(): bool
    {
        $now      = now()->timezone('Asia/Baghdad');
        $dayKey   = strtolower($now->englishDayOfWeek); // monday, tuesday...
        $timeNow  = $now->format('H:i');

        $raw      = static::get('weekly_schedule');
        $schedule = $raw ? json_decode($raw, true) : null;
        if (is_string($schedule)) {
            $schedule = json_decode($schedule, true);
        }

        if (is_array($schedule) && isset($schedule[$dayKey])) {
            $day = $schedule[$dayKey];
            if (empty($day['enabled'])) return false;
            $open  = $day['open']  ?? '00:00';
            $close = $day['close'] ?? '23:59';
            return $timeNow >= $open && $timeNow < $close;
        }

        // fallback: old open_time / close_time keys
        $open  = static::get('open_time',  '00:00');
        $close = static::get('close_time', '23:59');
        return $timeNow >= $open && $timeNow < $close;
    }
}
