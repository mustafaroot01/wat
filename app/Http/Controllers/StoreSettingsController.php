<?php

namespace App\Http\Controllers;

use App\Models\StoreSetting;
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
            'store_name'        => ['nullable', 'string', 'max:255'],
            'store_phone'       => ['nullable', 'string', 'max:50'],
            'store_address'     => ['nullable', 'string', 'max:500'],
            'thank_you_message' => ['nullable', 'string', 'max:500'],
            'logo'              => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
        ]);

        $keys = ['store_name', 'store_phone', 'store_address', 'thank_you_message'];

        foreach ($keys as $key) {
            if ($request->has($key)) {
                StoreSetting::set($key, $request->input($key));
            }
        }

        if ($request->hasFile('logo')) {
            $oldLogo = StoreSetting::get('logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }
            $path = $request->file('logo')->store('store', 'public');
            StoreSetting::set('logo', $path);
        }

        return response()->json([
            'message'  => 'تم حفظ الإعدادات بنجاح',
            'settings' => StoreSetting::allAsArray(),
        ]);
    }
}
