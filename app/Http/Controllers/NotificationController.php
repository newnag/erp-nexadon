<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get unread notifications count for the current user.
     */
    public function unreadCount()
    {
        $count = UserNotification::where('user_id', Auth::id())
            ->unread()
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Get recent notifications for the current user.
     */
    public function recent()
    {
        $notifications = UserNotification::where('user_id', Auth::id())
            ->with('activityLog.user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => UserNotification::where('user_id', Auth::id())->unread()->count(),
        ]);
    }

    /**
     * Get all notifications for the current user.
     */
    public function index(Request $request)
    {
        $query = UserNotification::where('user_id', Auth::id())
            ->with('activityLog.user')
            ->orderBy('created_at', 'desc');

        // Filter by read status
        if ($request->filled('status')) {
            if ($request->status === 'unread') {
                $query->unread();
            } elseif ($request->status === 'read') {
                $query->read();
            }
        }

        $notifications = $query->paginate(20)->withQueryString();

        return response()->json($notifications);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(UserNotification $notification)
    {
        // Ensure user owns this notification
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }

        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        UserNotification::where('user_id', Auth::id())
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Delete a notification.
     */
    public function destroy(UserNotification $notification)
    {
        // Ensure user owns this notification
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }

        $notification->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Delete all read notifications.
     */
    public function destroyAllRead()
    {
        UserNotification::where('user_id', Auth::id())
            ->read()
            ->delete();

        return response()->json(['success' => true]);
    }
}
