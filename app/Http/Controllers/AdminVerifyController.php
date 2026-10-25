<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Support\AdminDevices;
use App\Support\EmailOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/** The "enter the code we emailed you" page shown to an admin right after the password. */
class AdminVerifyController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        if ($request->session()->get('admin_verified_for') === $user->id || ! config('learnloop.admin_email_otp', true)) {
            return redirect()->route('admin.home');
        }

        $sendError = null;
        if (! EmailOtp::active($user->email, EmailOtp::ADMIN_LOGIN)
            && EmailOtp::cooldown($user->email, EmailOtp::ADMIN_LOGIN) === 0) {
            if (! EmailOtp::send($user->email, $user->name, EmailOtp::ADMIN_LOGIN)) {
                $sendError = 'We could not send the code. Check the mail settings (MAIL_* in .env) and press "Send a new code".';
            }
        }

        return view('auth.admin-verify', [
            'maskedEmail' => EmailOtp::mask($user->email),
            'wait' => EmailOtp::cooldown($user->email, EmailOtp::ADMIN_LOGIN),
            'sendError' => $sendError,
        ]);
    }

    public function resend(Request $request)
    {
        $user = $request->user();

        $wait = EmailOtp::cooldown($user->email, EmailOtp::ADMIN_LOGIN);
        if ($wait > 0) {
            return back()->withErrors(['code' => "Please wait {$wait} seconds before asking for another code."]);
        }
        if (! EmailOtp::send($user->email, $user->name, EmailOtp::ADMIN_LOGIN)) {
            return back()->withErrors(['code' => 'We could not send the email. Check the mail settings (MAIL_* in .env).']);
        }

        return back()->with('status', 'A new code was sent.');
    }

    public function verify(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'code' => ['required', 'string', 'regex:/^[0-9]{6}$/'],
        ], [
            'code.regex' => 'Enter the six-digit code exactly as shown.',
        ]);

        $key = 'admin-code:'.$user->id.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withErrors(['code' => "Too many attempts. Try again in {$seconds} seconds."]);
        }

        $error = EmailOtp::check($user->email, EmailOtp::ADMIN_LOGIN, $request->code);
        if ($error) {
            RateLimiter::hit($key, 300);
            ActivityLog::create(['user_id' => $user->id, 'action' => 'admin.verify_failed', 'description' => 'Wrong sign-in code from '.$request->ip()]);

            return back()->withErrors(['code' => $error]);
        }

        RateLimiter::clear($key);
        $request->session()->put('admin_verified_for', $user->id);

        $trusted = $request->boolean('trust');
        if ($trusted) {
            AdminDevices::trust($request, $user);
        }

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'admin.verified',
            'description' => 'Signed in with an email code from '.$request->ip().($trusted ? ' (device trusted for '.AdminDevices::DAYS.' days)' : ''),
        ]);

        return redirect()->to($request->session()->pull('admin_verify_intended', route('admin.home')));
    }
}
