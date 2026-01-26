<?php

namespace App\Http\Controllers\Api;

/**
 * @group Notifications
 *
 * APIs for managing user notifications. Users can list, read, and delete their notifications.
 * @authenticated
 */

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * List all notifications for the authenticated user.
     */
    public function index(Request $request)
    {
        $query = $request->user()->notifications();

        // Filter by read status
        if ($request->has('unread_only') && $request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }

        // Filter by type
        if ($request->has('type')) {
            $type = $request->get('type');
            $query->where('type', 'like', "%{$type}%");
        }

        $notifications = $query->latest()->paginate($request->get('per_page', 15));

        return NotificationResource::collection($notifications);
    }

    /**
     * Get count of unread notifications.
     */
    public function unreadCount(Request $request)
    {
        $count = $request->user()->unreadNotifications()->count();

        return response()->json([
            'unread_count' => $count,
        ]);
    }

    /**
     * Get a single notification.
     */
    public function show(Request $request, $id)
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        return new NotificationResource($notification);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read',
            'notification' => new NotificationResource($notification),
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'All notifications marked as read',
        ]);
    }

    /**
     * Delete a single notification.
     */
    public function destroy(Request $request, $id)
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted',
        ]);
    }

    /**
     * Delete all read notifications.
     */
    public function destroyRead(Request $request)
    {
        $deleted = $request->user()
            ->notifications()
            ->whereNotNull('read_at')
            ->delete();

        return response()->json([
            'message' => 'Read notifications deleted',
            'deleted_count' => $deleted,
        ]);
    }
}
