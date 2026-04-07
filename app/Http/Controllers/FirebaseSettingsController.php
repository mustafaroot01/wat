<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Models\FirebaseSetting;
use App\Services\FirebaseNotificationService;

class FirebaseSettingsController extends Controller
{
    public function index()
    {
        $settings = FirebaseSetting::first() ?? new FirebaseSetting();
        
        $jsonPath = storage_path('app/firebase-auth.json');
        $jsonExists = File::exists($jsonPath);
        
        return response()->json([
            'settings' => $settings,
            'json_exists' => $jsonExists,
            'project_id_from_json' => $jsonExists ? json_decode(File::get($jsonPath), true)['project_id'] ?? null : null
        ]);
    }

    public function storeSettings(Request $request)
    {
        $data = $request->validate([
            'api_key' => 'nullable|string',
            'auth_domain' => 'nullable|string',
            'project_id' => 'nullable|string',
            'storage_bucket' => 'nullable|string',
            'messaging_sender_id' => 'nullable|string',
            'app_id' => 'nullable|string',
            'measurement_id' => 'nullable|string',
            'default_topic' => 'required|string',
        ]);

        $settings = FirebaseSetting::first() ?? new FirebaseSetting();
        $settings->fill($data);
        $settings->save();

        return response()->json(['success' => true, 'message' => 'تم حفظ الإعدادات بنجاح.']);
    }

    public function uploadJson(Request $request)
    {
        $request->validate([
            'firebase_json' => 'required|file|mimes:json',
        ]);

        try {
            $file = $request->file('firebase_json');
            $content = json_decode(File::get($file->getRealPath()), true);

            if (!isset($content['project_id']) || !isset($content['private_key'])) {
                return response()->json(['message' => 'ملف JSON غير صالح.'], 422);
            }

            File::put(storage_path('app/firebase-auth.json'), json_encode($content, JSON_PRETTY_PRINT));

            return response()->json(['success' => true, 'message' => 'تم رفع ملف التوثيق بنجاح.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'فشل الرفع: ' . $e->getMessage()], 500);
        }
    }

    public function sendTest(FirebaseNotificationService $service)
    {
        $settings = FirebaseSetting::first();
        $topic = $settings->default_topic ?? 'all_users';

        $result = $service->sendToTopic(
            $topic, 
            'رسالة تجريبية 🧪', 
            'إذا كنت ترى هذا، فهذا يعني أن نظام الإشعارات مرتبط بـ Firebase بنجاح!'
        );

        return response()->json($result);
    }
}
