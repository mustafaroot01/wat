<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AppNotification;
use App\Http\Requests\StoreNotificationRequest;
use App\Http\Resources\NotificationResource;
use App\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    protected FirebaseNotificationService $firebaseService;

    public function __construct(FirebaseNotificationService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    public function index()
    {
        $notifications = AppNotification::orderBy('created_at', 'desc')->paginate(15);
        return NotificationResource::collection($notifications);
    }

    public function store(StoreNotificationRequest $request)
    {
        $data = $request->validated();
        $path = null;
        $imageUrl = null;

        try {
            DB::beginTransaction();

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('notifications', 'public');
                $data['image'] = $path;
                $imageUrl = asset('media/' . ltrim($path, '/'));
            }

            // Create notification as pending
            $data['delivery_status'] = 'pending';
            $notification = AppNotification::create($data);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            Log::error('Failed to create notification record: ' . $e->getMessage());
            return response()->json(['message' => 'فشل حفظ الإشعار.', 'error' => $e->getMessage()], 500);
        }

        // Get topic from settings
        $settings = \App\Models\FirebaseSetting::first();
        $topic = $settings->default_topic ?? 'all_users';

        // Send to Firebase
        $response = $this->firebaseService->sendToTopic(
            $topic,
            $notification->title,
            $notification->message,
            $imageUrl
        );

        if ($response['success']) {
            $notification->update([
                'delivery_status' => 'sent',
                'sent_at' => now()
            ]);
        } else {
            $notification->update([
                'delivery_status' => 'failed',
                'failure_reason' => $response['error'] ?? 'Unknown error'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم معالجة الإشعار.',
            'firebase_status' => $notification->delivery_status,
            'data' => new NotificationResource($notification)
        ], 201);
    }

    public function destroy(AppNotification $notification)
    {
        if ($notification->image) {
            Storage::disk('public')->delete($notification->image);
        }
        
        $notification->delete();

        return response()->json(['success' => true, 'message' => 'تم الحذف بنجاح.']);
    }
}
