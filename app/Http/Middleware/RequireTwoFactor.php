<?php

namespace App\Http\Middleware;

use App\Support\AdminDevices;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * (Kept under its old name so the "two-factor" route alias still works.)
 *
 * After the password, an admin must also enter a 6-digit code emailed to them,
 * once per sign-in, unless this browser was trusted for 30 days.
 * Turn it off in an emergency with config('learnloop.admin_email_otp') = false.
 */
class RequireTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user
            && $user->role === 'admin'
            && config('learnloop.admin_email_otp', true)
            && $request->session()->get('admin_verified_for') !== $user->id) {

            if (AdminDevices::isTrusted($request, $user)) {
                $request->session()->put('admin_verified_for', $user->id);

                return $next($request);
            }

            if ($request->isMethod('GET')) {
                $request->session()->put('admin_verify_intended', $request->fullUrl());
            }

            return redirect()->route('admin.verify');
        }

        return $next($request);
    }
}
