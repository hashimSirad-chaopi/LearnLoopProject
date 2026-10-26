<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Security now lives in Admin > Settings > Security. This controller only keeps the old
 * /admin/security address working and handles removing trusted browsers.
 */
class SecurityController extends Controller
{
    public function show()
    {
        return redirect()->route('admin.settings', ['tab' => 'security']);
    }

    public function removeDevice(Request $request, int $device)
    {
        $removed = DB::table('admin_trusted_devices')
            ->where('user_id', $request->user()->id)->where('id', $device)->delete();

        if ($removed) {
            ActivityLog::record('admin.device_removed', 'Stopped trusting a browser');
        }

        return redirect()->route('admin.settings', ['tab' => 'security'])
            ->with('success', 'That browser will ask for an email code next time.');
    }

    public function removeAllDevices(Request $request)
    {
        $count = DB::table('admin_trusted_devices')->where('user_id', $request->user()->id)->delete();
        ActivityLog::record('admin.device_removed', "Stopped trusting all browsers ({$count})");

        return redirect()->route('admin.settings', ['tab' => 'security'])
            ->with('success', 'All browsers will ask for an email code next time.');
    }
}
