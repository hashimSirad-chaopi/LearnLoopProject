<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * The code prompt shown right after the password, for admins with
 * two-step verification turned on.
 */
class TwoFactorController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        // Nothing to do if it is off, or already passed in this session.
        if (! $user->two_factor_confirmed_at || $request->session()->get('two_factor_passed_for') === $user->id) {
            return redirect()->route('admin.home');
        }

        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request)
    {
        $user = $request->user();
        $request->validate(['code' => 'required|string|max:32']);

        $key = 'two-factor:'.$user->id.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withErrors(['code' => "Too many attempts. Try again in {$seconds} seconds."]);
        }

        $input = trim($request->input('code'));
        $valid = Totp::verify($user->two_factor_secret, $input);
        $usedRecovery = false;

        if (! $valid) {
            // A recovery code (single use) works when the phone is lost.
            $codes = $user->two_factor_recovery_codes ?? [];
            $normalized = Str::lower(str_replace(' ', '', $input));
            foreach ($codes as $i => $stored) {
                if (hash_equals(Str::lower($stored), $normalized)) {
                    unset($codes[$i]);
                    $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();
                    $valid = $usedRecovery = true;
                    break;
                }
            }
        }

        if (! $valid) {
            RateLimiter::hit($key, 60);
            ActivityLog::create(['user_id' => $user->id, 'action' => 'admin.2fa_failed', 'description' => 'Wrong two-step code from '.$request->ip()]);

            return back()->withErrors(['code' => 'That code is not correct.']);
        }

        RateLimiter::clear($key);
        $request->session()->put('two_factor_passed_for', $user->id);

        if ($usedRecovery) {
            ActivityLog::create(['user_id' => $user->id, 'action' => 'admin.2fa_recovery_used', 'description' => 'Signed in with a recovery code ('.count($user->two_factor_recovery_codes ?? []).' left)']);
        }

        return redirect()->to($request->session()->pull('two_factor_intended', route('admin.home')));
    }
}
