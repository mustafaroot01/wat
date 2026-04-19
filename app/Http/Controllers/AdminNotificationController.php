<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    /**
     * Get paginated notifications
     */
    public function index(Request $request)
    {
        $perPage = max(1, min((int) $request->get('per_page', 20), 100));

        $notifications = AdminNotification::with('order')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data'         => $notifications->items(),
            'total'        => $notifications->total(),
            'current_page' => $notifications->currentPage(),
            'last_page'    => $notifications->lastPage(),
        ]);
    }

    /**
     * Get unread count + latest unread notifications (for bell dropdown)
     */
    public function unread()
    {
        $unreadCount = AdminNotification::unread()->count();
        $latest = AdminNotification::unread()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => $latest,
        ]);
    }

    /**
     * Mark a single notification as read
     */
    public function markAsRead($id)
    {
        $notification = AdminNotification::findOrFail($id);
        $notification->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        AdminNotification::unread()->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * Delete all notifications
     */
    public function destroyAll()
    {
        AdminNotification::truncate();

        return response()->json(['success' => true, 'message' => 'تم حذف جميع الإشعارات']);
    }

    /**
     * Delete a notification
     */
    public function destroy($id)
    {
        AdminNotification::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف الإشعار']);
    }
}
