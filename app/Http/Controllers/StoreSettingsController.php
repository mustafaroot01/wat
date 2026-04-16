<?php

namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\StoreSetting;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StoreSettingsController extends Controller
{
    public function index()
    {
        return response()->json(StoreSetting::allAsArray());
    }

    public function update(Request $request)
    {
        $request->validate([
            'store_name'            => ['nullable', 'string', 'max:255'],
            'store_phone'           => ['nullable', 'string', 'max:50'],
            'store_address'         => ['nullable', 'string', 'max:500'],
            'thank_you_message'     => ['nullable', 'string', 'max:500'],
            'minimum_order_amount'  => ['nullable', 'numeric', 'min:0'],
            'logo'                  => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:30720'],
            'open_time'             => ['nullable', 'date_format:H:i'],
            'close_time'            => ['nullable', 'date_format:H:i'],
            'contact_phone2'        => ['nullable', 'string', 'max:50'],
            'contact_instagram'     => ['nullable', 'string', 'max:255'],
            'contact_facebook'      => ['nullable', 'string', 'max:255'],
            'about_us_description'  => ['nullable', 'string'],
            'privacy_policy'        => ['nullable', 'string'],
            'min_version_android'   => ['nullable', 'string', 'max:20'],
            'current_version_android' => ['nullable', 'string', 'max:20'],
            'update_url_android'    => ['nullable', 'string', 'max:500'],
            'force_update_android'  => ['nullable', 'in:0,1'],
            'min_version_ios'       => ['nullable', 'string', 'max:20'],
            'current_version_ios'   => ['nullable', 'string', 'max:20'],
            'update_url_ios'        => ['nullable', 'string', 'max:500'],
            'force_update_ios'      => ['nullable', 'in:0,1'],
            'telegram_bot_token'    => ['nullable', 'string', 'max:500'],
            'telegram_chat_id'      => ['nullable', 'string', 'max:255'],
            'telegram_enabled'      => ['nullable', 'in:0,1'],
        ]);

        $keys = [
            'store_name', 'store_phone', 'store_address', 'thank_you_message', 'minimum_order_amount',
            'contact_phone2', 'contact_instagram', 'contact_facebook', 'about_us_description', 'privacy_policy',
            'min_version_android', 'current_version_android', 'update_url_android', 'force_update_android',
            'min_version_ios', 'current_version_ios', 'update_url_ios', 'force_update_ios',
            'telegram_bot_token', 'telegram_chat_id', 'telegram_enabled',
        ];

        foreach ($keys as $key) {
            if ($request->has($key)) {
                StoreSetting::set($key, $request->input($key));
            }
        }

        if ($request->has('weekly_schedule')) {
            $ws = $request->input('weekly_schedule');
            StoreSetting::set('weekly_schedule', is_array($ws) ? json_encode($ws) : $ws);
        }

        if ($request->hasFile('logo')) {
            $oldLogo = StoreSetting::get('logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }
            $path = ImageHelper::compressAndStore($request->file('logo'), 'store');
            StoreSetting::set('logo', $path);
        }

        return response()->json([
            'message'  => 'تم حفظ الإعدادات بنجاح',
            'settings' => StoreSetting::allAsArray(),
        ]);
    }

    public function testTelegramConnection(Request $request)
    {
        $request->validate([
            'bot_token' => 'required|string',
            'chat_id' => 'required|string',
        ]);

        try {
            $response = \Illuminate\Support\Facades\Http::post(
                "https://api.telegram.org/bot{$request->bot_token}/sendMessage",
                [
                    'chat_id' => $request->chat_id,
                    'text' => "✅ تم الاتصال بنجاح!\n\nبوت التيليجرام جاهز لاستقبال الطلبات.",
                ]
            );

            if ($response->successful()) {
                return response()->json(['success' => true, 'message' => 'تم الاتصال بنجاح! ✓']);
            }

            return response()->json([
                'success' => false,
                'message' => 'فشل الاتصال. تحقق من Bot Token و Chat ID'
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ: ' . $e->getMessage()
            ], 400);
        }
    }
}
