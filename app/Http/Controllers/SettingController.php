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
                'whatsapp_api_key' => Setting::get('whatsapp_api_key', ''),
                // Future settings can be added here
            ]
        ]);
    }

    /**
     * Save/Update settings
     */
    public function store(Request $request)
    {
        $request->validate([
            'whatsapp_api_key' => 'nullable|string',
        ]);

        foreach ($request->only(['whatsapp_api_key']) as $key => $value) {
            Setting::set($key, $value);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ الإعدادات بنجاح.'
        ]);
    }
}
