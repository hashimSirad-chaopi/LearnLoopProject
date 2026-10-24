<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\EmailOtp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Forgot password" by email code (replaces the old reset link).
 * Step 1 here: ask for the email and send a 6-digit code.
 * Step 2 is NewPasswordController: type the code and a new password.
 */
class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $email = strtolower(trim($request->email));
        $user = User::whereRaw('lower(email) = ?', [$email])->first();

        // Same answer whether or not the account exists, so nobody can probe for emails.
        if ($user && EmailOtp::cooldown($email, EmailOtp::PASSWORD_RESET) === 0) {
            if (! EmailOtp::send($user->email, $user->name, EmailOtp::PASSWORD_RESET)) {
                return back()->withInput()->withErrors(['email' => 'We could not send the email right now. Try again in a moment.']);
            }
        }

        $request->session()->put('otp_email', $email);

        return redirect()->route('password.reset')
            ->with('status', 'If an account exists for that email, a 6-digit code is on its way. It works for '.EmailOtp::TTL_MINUTES.' minutes.');
    }

    /** "Send a new code" on the code page. */
    public function resend(Request $request): RedirectResponse
    {
        $email = $request->session()->get('otp_email');
        if (! $email) {
            return redirect()->route('password.request');
        }

        $wait = EmailOtp::cooldown($email, EmailOtp::PASSWORD_RESET);
        if ($wait > 0) {
            return back()->withErrors(['code' => "Please wait {$wait} seconds before asking for another code."]);
        }

        $user = User::whereRaw('lower(email) = ?', [$email])->first();
        if ($user && ! EmailOtp::send($user->email, $user->name, EmailOtp::PASSWORD_RESET)) {
            return back()->withErrors(['code' => 'We could not send the email right now. Try again in a moment.']);
        }

        return back()->with('status', 'If an account exists for that email, a new code is on its way.');
    }
}
