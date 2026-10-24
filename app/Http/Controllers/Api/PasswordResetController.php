<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\EmailOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

/**
 * Forgot password for the Flutter app, with a 6-digit code sent by email.
 *
 *   POST /forgot-password          {email}                      -> sends a code
 *   POST /forgot-password/verify   {email, code}                -> {reset_token}
 *   POST /forgot-password/reset    {email, reset_token, password, password_confirmation}
 */
class PasswordResetController extends Controller
{
    private function find(string $email): ?User
    {
        return User::whereRaw('lower(email) = ?', [strtolower(trim($email))])->first();
    }

    /** Always answers the same, so the endpoint cannot be used to find out who has an account. */
    public function send(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = $this->find($request->email);
        $wait = EmailOtp::cooldown($request->email, EmailOtp::PASSWORD_RESET);

        if ($user && $wait === 0 && ! EmailOtp::send($user->email, $user->name, EmailOtp::PASSWORD_RESET)) {
            return response()->json(['message' => 'We could not send the email right now. Try again in a moment.'], 503);
        }

        return response()->json([
            'message' => 'If an account exists for that email, a 6-digit code is on its way.',
            'resend_in' => $wait > 0 ? $wait : EmailOtp::COOLDOWN_SECONDS,
            'expires_in_minutes' => EmailOtp::TTL_MINUTES,
        ]);
    }

    public function verify(Request $request)
    {
        $request->validate(['email' => 'required|email', 'code' => 'required|string|max:12']);

        $key = 'api-reset-code:'.strtolower($request->email).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json(['message' => 'Too many tries. Please wait a few minutes.'], 429);
        }

        $error = $this->find($request->email)
            ? EmailOtp::check($request->email, EmailOtp::PASSWORD_RESET, $request->code, consume: false)
            : 'That code is not correct.';

        if ($error) {
            RateLimiter::hit($key, 600);

            return response()->json(['message' => $error, 'errors' => ['code' => [$error]]], 422);
        }

        RateLimiter::clear($key);

        return response()->json([
            'message' => 'Code accepted. Choose a new password.',
            'reset_token' => EmailOtp::issueToken($request->email, EmailOtp::PASSWORD_RESET),
        ]);
    }

    public function reset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'reset_token' => 'required|string|max:100',
            'password' => 'required|string|min:8|max:255|confirmed',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $user = $this->find($request->email);
        if (! $user || ! EmailOtp::useToken($request->email, EmailOtp::PASSWORD_RESET, $request->reset_token)) {
            return response()->json(['message' => 'This reset has expired. Start again and ask for a new code.'], 422);
        }

        $user->update(['password' => $request->password]); // hashed by the model cast

        // Every phone and browser signed in with the old password must log in again.
        $user->tokens()->delete();
        DB::table('admin_trusted_devices')->where('user_id', $user->id)->delete();

        ActivityLog::create(['user_id' => $user->id, 'action' => 'password.reset', 'description' => 'Password reset with an email code (app) from '.$request->ip()]);

        return response()->json(['message' => 'Password changed. You can log in now.']);
    }
}
