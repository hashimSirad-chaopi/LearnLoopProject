<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

/**
 * A member's alerts. Each one has a `source`:
 *   'admin' = sent or triggered by an administrator
 *   'user'  = member activity (coach lessons, requests, ...)
 * The app shows them on two tabs; ?source=admin|user narrows the list.
 */
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $source = $request->query('source');

        $notifications = Notification::where('user_id', $userId)
            ->when(
                in_array($source, [Notification::SOURCE_ADMIN, Notification::SOURCE_USER], true),
                fn ($q) => $q->where('source', $source)
            )
            ->latest()
            ->get();

        return response()->json([
            'notifications' => $notifications,
            'unread' => [
                'admin' => $this->unread($userId, Notification::SOURCE_ADMIN),
                'user' => $this->unread($userId, Notification::SOURCE_USER),
            ],
        ]);
    }

    /**
     * GET /notifications/summary: two tiny numbers, so the app can check for news
     * every few seconds without downloading the whole list each time. `key` changes
     * when a new alert arrives or one is read; the app fetches the list only then.
     */
    public function summary(Request $request)
    {
        $userId = $request->user()->id;

        $latestId = (int) Notification::where('user_id', $userId)->max('id');
        $unread = Notification::where('user_id', $userId)->whereNull('read_at')->count();

        return response()->json([
            'latest_id' => $latestId,
            'unread' => $unread,
            'key' => $latestId.':'.$unread,
        ]);
    }

    public function markRead(Request $request, Notification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->markAsRead();

        return response()->json(['notification' => $notification->fresh()]);
    }

    /** PATCH /notifications/read-all (optionally ?source=admin|user) */
    public function markAllRead(Request $request)
    {
        $source = $request->query('source');

        Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->when(
                in_array($source, [Notification::SOURCE_ADMIN, Notification::SOURCE_USER], true),
                fn ($q) => $q->where('source', $source)
            )
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Marked as read']);
    }

    private function unread(int $userId, string $source): int
    {
        return Notification::where('user_id', $userId)
            ->where('source', $source)
            ->whereNull('read_at')
            ->count();
    }
}
