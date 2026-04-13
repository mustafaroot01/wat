<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Get all general settings
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'settings' => [
                'otpiq_api_key'        => Setting::get('otpiq_api_key', ''),
                'otp_credits'          => (int) Setting::get('otp_credits', 0),
                'notification_credits' => (int) Setting::get('notification_credits', 0),
            ]
        ]);
    }

    /**
     * Save/Update settings
     */
    public function store(Request $request)
    {
        $request->validate([
            'otpiq_api_key'        => 'nullable|string|max:200',
            'otp_credits'          => 'nullable|integer|min:0',
            'notification_credits' => 'nullable|integer|min:0',
        ]);

        foreach ($request->only(['otpiq_api_key', 'otp_credits', 'notification_credits']) as $key => $value) {
            Setting::set($key, $value);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ الإعدادات بنجاح.'
        ]);
    }

    /**
     * Add credits on top of existing value (top-up)
     */
    public function topUp(Request $request)
    {
        $request->validate([
            'type'   => 'required|in:otp,notification',
            'amount' => 'required|integer|min:1|max:100000',
        ]);

        $key     = $request->type === 'otp' ? 'otp_credits' : 'notification_credits';
        $current = (int) Setting::get($key, 0);
        $new     = $current + (int) $request->amount;

        Setting::set($key, $new);

        return response()->json([
            'success'   => true,
            'message'   => 'تمت إضافة الرصيد بنجاح.',
            'new_value' => $new,
        ]);
    }
}
