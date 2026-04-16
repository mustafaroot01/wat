<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AppNotification;
use App\Http\Requests\StoreNotificationRequest;
use App\Http\Resources\NotificationResource;
use App\Services\ActivityLogService;
use App\Services\FirebaseNotificationService;
use App\Models\Setting;
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
        return NotificationResource::collection($notifications)
            ->additional(['has_more' => $notifications->hasMorePages()]);
    }

    public function store(StoreNotificationRequest $request)
    {
        // Block if no notification credits
        $credits = (int) Setting::get('notification_credits', 0);
        if ($credits <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'رصيد الإشعارات نفد. يرجى إضافة رصيد من صفحة الإعدادات.',
                'code'    => 'no_credits',
            ], 403);
        }

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
        $topic = optional($settings)->default_topic ?? 'all_users';

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
            // Decrement notification credits
            Setting::set('notification_credits', max(0, $credits - 1));
            
            // Log activity
            ActivityLogService::log(
                $request->user(),
                'created',
                'Notification',
                $notification->id,
                [],
                ['title' => $notification->title, 'status' => 'sent'],
                ['name' => $notification->title],
                $request
            );
        } else {
            $notification->update([
                'delivery_status' => 'failed',
                'failure_reason' => $response['error'] ?? 'Unknown error'
            ]);
            
            // Log failed notification attempt
            ActivityLogService::log(
                $request->user(),
                'created',
                'Notification',
                $notification->id,
                [],
                ['title' => $notification->title, 'status' => 'failed'],
                ['name' => $notification->title],
                $request
            );
        }

        return response()->json([
            'success'          => true,
            'message'          => 'تم معالجة الإشعار.',
            'firebase_status'  => $notification->delivery_status,
            'credits_remaining'=> (int) Setting::get('notification_credits', 0),
            'data'             => new NotificationResource($notification)
        ], 201);
    }

    public function destroy(AppNotification $notification)
    {
        $notificationTitle = $notification->title;
        
        if ($notification->image) {
            Storage::disk('public')->delete($notification->image);
        }
        
        $notification->delete();

        // Log activity
        ActivityLogService::log(
            request()->user(),
            'deleted',
            'Notification',
            null,
            [],
            [],
            ['name' => $notificationTitle],
            request()
        );

        return response()->json(['success' => true, 'message' => 'تم الحذف بنجاح.']);
    }
}
