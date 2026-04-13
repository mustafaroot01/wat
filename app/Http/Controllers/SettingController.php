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
                'arqam_api_key' => Setting::get('arqam_api_key', Setting::get('whatsapp_api_key', '')),
            ]
        ]);
    }

    /**
     * Save/Update settings
     */
    public function store(Request $request)
    {
        $request->validate([
            'arqam_api_key' => 'nullable|string|max:200',
        ]);

        foreach ($request->only(['arqam_api_key']) as $key => $value) {
            Setting::set($key, $value);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ الإعدادات بنجاح.'
        ]);
    }
}
