<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Remembers when a signed-in member (or admin) was last active, which is what
 * the Active / Offline indicator is built from.
 *
 * It writes at most once every 15 seconds per person, and straight to the
 * table so the account's "updated at" does not change.
 */
class TrackLastSeen
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->offline_since !== null) {
            // Back in the app (or the panel) after being offline: active again.
            // The offline call itself sets the marker again right after this.
            DB::table('users')->where('id', $user->id)->update(['last_seen_at' => now(), 'offline_since' => null]);
        } elseif ($user && ($user->last_seen_at === null || $user->last_seen_at->lt(now()->subSeconds(15)))) {
            DB::table('users')->where('id', $user->id)->update(['last_seen_at' => now()]);
        }

        return $next($request);
    }
}
