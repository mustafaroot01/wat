<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = AppNotification::where('delivery_status', 'sent')
            ->orderBy('sent_at', 'desc')
            ->paginate($request->get('per_page', 15));

        $data = $notifications->getCollection()->map(fn ($n) => [
            'id'         => $n->id,
            'title'      => $n->title,
            'message'    => $n->message,
            'image_url'  => $n->image ? asset('media/' . ltrim($n->image, '/')) : null,
            'sent_at'    => $n->sent_at?->format('Y-m-d H:i:s'),
            'created_at' => $n->created_at->format('Y-m-d H:i:s'),
        ]);

        return response()->json([
            'success'  => true,
            'data'     => $data,
            'has_more' => $notifications->hasMorePages(),
            'meta'     => [
                'current_page' => $notifications->currentPage(),
                'per_page'     => $notifications->perPage(),
                'total'        => $notifications->total(),
            ],
        ]);
    }

    public function show(AppNotification $notification)
    {
        if ($notification->delivery_status !== 'sent') {
            return response()->json(['success' => false, 'message' => 'الإشعار غير متاح.'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'         => $notification->id,
                'title'      => $notification->title,
                'message'    => $notification->message,
                'image_url'  => $notification->image ? asset('media/' . ltrim($notification->image, '/')) : null,
                'sent_at'    => $notification->sent_at?->format('Y-m-d H:i:s'),
                'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}
