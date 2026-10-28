<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\User;
use App\Support\AdminAlerts;
use Illuminate\Http\Request;

/**
 * Admin > Notifications, split three ways so the two kinds never mix:
 *
 *   attention = the admin's OWN notifications (work waiting for you)
 *   sent      = notices an admin sent or triggered for members (source 'admin')
 *   activity  = notices members got from each other / the app (source 'user')
 */
class NotificationLogController extends Controller
{
    public function index(Request $request)
    {
        $tab = in_array($request->query('tab'), ['attention', 'sent', 'activity'], true)
            ? $request->query('tab')
            : 'attention';
        $status = in_array($request->query('read'), ['unread', 'read'], true) ? $request->query('read') : 'all';

        $notifications = null;
        if ($tab !== 'attention') {
            $notifications = Notification::with('user')
                ->where('source', $tab === 'sent' ? Notification::SOURCE_ADMIN : Notification::SOURCE_USER)
                ->when($status === 'unread', fn ($q) => $q->whereNull('read_at'))
                ->when($status === 'read', fn ($q) => $q->whereNotNull('read_at'))
                ->latest()
                ->paginate(20)
                ->withQueryString();
        }

        return view('admin.notifications', [
            'tab' => $tab,
            'status' => $status,
            'attention' => AdminAlerts::items(),
            'attentionTotal' => AdminAlerts::total(),
            'sentCount' => Notification::fromAdmin()->count(),
            'activityCount' => Notification::fromMembers()->count(),
            'notifications' => $notifications,
            'users' => $tab === 'sent' ? User::where('role', '!=', 'admin')->orderBy('name')->get(['id', 'name', 'email']) : collect(),
        ]);
    }

    /** Send a notice to one member. It lands on the "Admin" tab in the app. */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:150',
            'message' => 'required|string|max:500',
        ]);

        Notification::notifyMember($validated['user_id'], $validated['title'], $validated['message'], 'user', $validated['user_id']);
        ActivityLog::record('notification.sent', "Sent a notice: \"{$validated['title']}\"");

        return redirect()->route('admin.notifications', ['tab' => 'sent'])->with('success', 'Notice sent. The user sees it under Admin in the app.');
    }

    public function destroy(Notification $notification)
    {
        $tab = $notification->source === Notification::SOURCE_ADMIN ? 'sent' : 'activity';
        $title = $notification->title;
        $notification->delete();
        ActivityLog::record('notification.deleted', "Deleted a notification: \"{$title}\"");

        return redirect()->route('admin.notifications', ['tab' => $tab])->with('success', 'Notification deleted.');
    }
}
