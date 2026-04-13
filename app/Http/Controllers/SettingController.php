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
                'otp_provider'              => Setting::get('otp_provider',              'arqam'),
                'arqam_api_key'             => Setting::get('arqam_api_key',             ''),
                'twilio_account_sid'        => Setting::get('twilio_account_sid',        ''),
                'twilio_auth_token'         => Setting::get('twilio_auth_token',         ''),
                'twilio_verify_service_sid' => Setting::get('twilio_verify_service_sid', ''),
                'otpiq_api_key'             => Setting::get('otpiq_api_key',             ''),
            ]
        ]);
    }

    /**
     * Save/Update settings
     */
    public function store(Request $request)
    {
        $request->validate([
            'otp_provider'              => 'nullable|in:twilio,otpiq,arqam',
            'arqam_api_key'             => 'nullable|string|max:200',
            'twilio_account_sid'        => 'nullable|string|max:100',
            'twilio_auth_token'         => 'nullable|string|max:100',
            'twilio_verify_service_sid' => 'nullable|string|max:100',
            'otpiq_api_key'             => 'nullable|string|max:200',
        ]);

        foreach ($request->only([
            'otp_provider', 'arqam_api_key', 'twilio_account_sid',
            'twilio_auth_token', 'twilio_verify_service_sid', 'otpiq_api_key',
        ]) as $key => $value) {
            Setting::set($key, $value);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ الإعدادات بنجاح.'
        ]);
    }
}
