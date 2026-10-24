<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Browsers an admin chose to trust for 30 days, so the email code is not asked every time. */
class AdminDevices
{
    public const COOKIE = 'll_trusted_device';
    public const DAYS = 30;

    private static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    /** Is this browser trusted for this admin? Refreshes "last used". */
    public static function isTrusted(Request $request, User $user): bool
    {
        $token = $request->cookie(self::COOKIE);
        if (! is_string($token) || $token === '') {
            return false;
        }

        $row = DB::table('admin_trusted_devices')
            ->where('user_id', $user->id)
            ->where('token_hash', self::hash($token))
            ->where('expires_at', '>', now())
            ->first();

        if (! $row) {
            return false;
        }

        DB::table('admin_trusted_devices')->where('id', $row->id)->update(['last_used_at' => now()]);

        return true;
    }

    /** Remember this browser. Queues the cookie on the response. */
    public static function trust(Request $request, User $user): void
    {
        $token = Str::random(64);

        DB::table('admin_trusted_devices')->insert([
            'user_id' => $user->id,
            'token_hash' => self::hash($token),
            'label' => self::label((string) $request->userAgent()),
            'ip' => $request->ip(),
            'last_used_at' => now(),
            'expires_at' => now()->addDays(self::DAYS),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Cookie::queue(Cookie::make(self::COOKIE, $token, self::DAYS * 24 * 60, '/', null, $request->isSecure(), true, false, 'lax'));
    }

    /** The id of the row for the browser making this request, if trusted. */
    public static function currentId(Request $request, User $user): ?int
    {
        $token = $request->cookie(self::COOKIE);
        if (! is_string($token) || $token === '') {
            return null;
        }

        return DB::table('admin_trusted_devices')
            ->where('user_id', $user->id)->where('token_hash', self::hash($token))->value('id');
    }

    public static function label(string $ua): string
    {
        $browser = match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Chrome/') => 'Chrome',
            str_contains($ua, 'Safari/') => 'Safari',
            default => 'Browser',
        };
        $os = match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Mac OS') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'unknown device',
        };

        return "{$browser} on {$os}";
    }
}
