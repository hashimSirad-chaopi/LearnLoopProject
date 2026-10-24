<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Support\AdminDevices;
use App\Support\EmailOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin > Security: the emailed sign-in code is always on. Here the admin sees
 * which browsers are trusted (and removes them) and the recent sign-in events.
 */
class SecurityController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        $devices = DB::table('admin_trusted_devices')
            ->where('user_id', $user->id)->where('expires_at', '>', now())
            ->orderByDesc('last_used_at')->get();

        $events = ActivityLog::where('user_id', $user->id)
            ->whereIn('action', ['admin.verified', 'admin.verify_failed', 'admin.device_removed', 'password.reset'])
            ->latest()->take(10)->get();

        return view('admin.security', [
            'enabled' => (bool) config('learnloop.admin_email_otp', true),
            'maskedEmail' => EmailOtp::mask($user->email),
            'devices' => $devices,
            'currentDevice' => AdminDevices::currentId($request, $user),
            'events' => $events,
        ]);
    }

    public function removeDevice(Request $request, int $device)
    {
        $removed = DB::table('admin_trusted_devices')
            ->where('user_id', $request->user()->id)->where('id', $device)->delete();

        if ($removed) {
            ActivityLog::record('admin.device_removed', 'Stopped trusting a browser');
        }

        return back()->with('success', 'That browser will ask for an email code next time.');
    }

    public function removeAllDevices(Request $request)
    {
        $count = DB::table('admin_trusted_devices')->where('user_id', $request->user()->id)->delete();
        ActivityLog::record('admin.device_removed', "Stopped trusting all browsers ({$count})");

        return back()->with('success', 'All browsers will ask for an email code next time.');
    }
}
