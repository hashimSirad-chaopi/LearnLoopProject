<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\EmailOtp;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/** Step 2 of "forgot password": the emailed code plus the new password. */
class NewPasswordController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get('otp_email');
        if (! $email) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password', [
            'maskedEmail' => EmailOtp::mask($email),
            'wait' => EmailOtp::cooldown($email, EmailOtp::PASSWORD_RESET),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $email = $request->session()->get('otp_email');
        if (! $email) {
            return redirect()->route('password.request');
        }

        $request->validate([
            'code' => ['required', 'string', 'max:12'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $key = 'reset-code:'.$email.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return back()->withErrors(['code' => 'Too many tries. Please wait a few minutes.']);
        }

        $user = User::whereRaw('lower(email) = ?', [$email])->first();
        $error = $user ? EmailOtp::check($email, EmailOtp::PASSWORD_RESET, $request->code) : 'That code is not correct.';

        if ($error) {
            RateLimiter::hit($key, 600);

            return back()->withErrors(['code' => $error]);
        }

        RateLimiter::clear($key);

        $user->forceFill([
            'password' => $request->password, // hashed by the model cast
            'remember_token' => Str::random(60),
        ])->save();

        // Anyone signed in with the old password (phones, trusted admin browsers) must sign in again.
        $user->tokens()->delete();
        DB::table('admin_trusted_devices')->where('user_id', $user->id)->delete();

        ActivityLog::create(['user_id' => $user->id, 'action' => 'password.reset', 'description' => 'Password reset with an email code from '.$request->ip()]);
        event(new PasswordReset($user));

        $request->session()->forget('otp_email');

        return redirect()->route('login')->with('status', 'Your password was changed. You can sign in now.');
    }
}
