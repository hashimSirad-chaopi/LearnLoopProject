<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * 6-digit codes sent by email. Used for "forgot password" (members and admins)
 * and for the admin's sign-in check.
 *
 * - The code is stored only as an HMAC, expires after 10 minutes and dies after
 *   5 wrong tries.
 * - A new code can be sent once every 60 seconds.
 * - Each (email, purpose) pair has one live code; asking again replaces it.
 */
class EmailOtp
{
    public const PASSWORD_RESET = 'password_reset';
    public const ADMIN_LOGIN = 'admin_login';

    public const TTL_MINUTES = 10;
    public const MAX_ATTEMPTS = 5;
    public const COOLDOWN_SECONDS = 60;
    public const TOKEN_MINUTES = 15;

    private static function norm(string $email): string
    {
        return Str::lower(trim($email));
    }

    private static function hash(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }

    private static function row(string $email, string $purpose)
    {
        return DB::table('email_otps')->where('email', self::norm($email))->where('purpose', $purpose)->first();
    }

    /** Seconds until another code may be sent (0 = now). */
    public static function cooldown(string $email, string $purpose): int
    {
        $row = self::row($email, $purpose);
        if (! $row || ! $row->last_sent_at) {
            return 0;
        }
        $ago = now()->timestamp - \Illuminate\Support\Carbon::parse($row->last_sent_at)->timestamp;

        return max(0, self::COOLDOWN_SECONDS - $ago);
    }

    /** Is there a code that can still be typed in? */
    public static function active(string $email, string $purpose): bool
    {
        $row = self::row($email, $purpose);

        return $row && $row->code_hash && now()->lt($row->expires_at) && $row->attempts < self::MAX_ATTEMPTS;
    }

    /** Make a fresh code, store it and email it. False when the email could not be sent. */
    public static function send(string $email, string $name, string $purpose): bool
    {
        $email = self::norm($email);
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('email_otps')->updateOrInsert(
            ['email' => $email, 'purpose' => $purpose],
            [
                'code_hash' => self::hash($email.'|'.$purpose.'|'.$code),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(self::TTL_MINUTES),
                'last_sent_at' => now(),
                'token_hash' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $subject = $purpose === self::ADMIN_LOGIN
            ? "Your LearnLoop admin sign-in code: {$code}"
            : "Your LearnLoop password reset code: {$code}";

        try {
            $html = view('emails.otp', [
                'name' => $name,
                'code' => $code,
                'minutes' => self::TTL_MINUTES,
                'purpose' => $purpose,
            ])->render();

            Mail::html($html, fn ($m) => $m->to($email)->subject($subject));

            return true;
        } catch (\Throwable $e) {
            Log::warning('OTP email failed: '.$e->getMessage());
            self::forget($email, $purpose);

            return false;
        }
    }

    /**
     * Check a typed code. Returns null when it is right (and uses it up),
     * otherwise the message to show.
     */
    public static function check(string $email, string $purpose, string $code, bool $consume = true): ?string
    {
        $email = self::norm($email);
        $row = self::row($email, $purpose);
        $code = preg_replace('/\D/', '', $code) ?? '';

        if (! $row || ! $row->code_hash) {
            return 'No code is waiting. Ask for a new one.';
        }
        if (now()->gte($row->expires_at)) {
            self::forget($email, $purpose);

            return 'That code has expired. Ask for a new one.';
        }
        if ($row->attempts >= self::MAX_ATTEMPTS) {
            self::forget($email, $purpose);

            return 'Too many wrong codes. Ask for a new one.';
        }

        if (strlen($code) !== 6 || ! hash_equals($row->code_hash, self::hash($email.'|'.$purpose.'|'.$code))) {
            $attempts = $row->attempts + 1;
            DB::table('email_otps')->where('id', $row->id)->update(['attempts' => $attempts]);
            if ($attempts >= self::MAX_ATTEMPTS) {
                self::forget($email, $purpose);

                return 'Too many wrong codes. Ask for a new one.';
            }
            $left = self::MAX_ATTEMPTS - $attempts;

            return "That code is not correct. {$left} ".Str::plural('try', $left).' left.';
        }

        if ($consume) {
            self::forget($email, $purpose);
        }

        return null;
    }

    /** App flow: after the code is right, hand out a one-time token for the "new password" step. */
    public static function issueToken(string $email, string $purpose): string
    {
        $token = Str::random(48);
        DB::table('email_otps')
            ->where('email', self::norm($email))->where('purpose', $purpose)
            ->update([
                'code_hash' => null,
                'token_hash' => self::hash($token),
                'expires_at' => now()->addMinutes(self::TOKEN_MINUTES),
                'updated_at' => now(),
            ]);

        return $token;
    }

    /** True once, for the token from [issueToken]. */
    public static function useToken(string $email, string $purpose, string $token): bool
    {
        $row = self::row($email, $purpose);
        if (! $row || ! $row->token_hash || now()->gte($row->expires_at)) {
            return false;
        }
        if (! hash_equals($row->token_hash, self::hash($token))) {
            return false;
        }
        self::forget($email, $purpose);

        return true;
    }

    public static function forget(string $email, string $purpose): void
    {
        DB::table('email_otps')->where('email', self::norm($email))->where('purpose', $purpose)->delete();
    }

    /** "ha*****@gmail.com" for the "we sent a code to ..." line. */
    public static function mask(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return Str::substr($local, 0, 2).str_repeat('*', max(1, strlen($local) - 2)).'@'.$domain;
    }
}
