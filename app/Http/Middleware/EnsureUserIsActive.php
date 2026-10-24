<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API guard: a suspended user's, or an administrator's, Sanctum tokens stop working.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // An admin token must never work on the member API (covers tokens issued before this rule existed).
        // 401 makes the app clear its saved session and return to the login screen.
        if ($user && $user->role === 'admin') {
            $user->tokens()->delete();

            return response()->json(['message' => 'Administrator accounts cannot use the app.', 'code' => 'admin_not_allowed'], 401);
        }

        $user?->liftSuspensionIfOver();   // a timed suspension that has run out

        if ($user && $user->status !== 'active') {
            $user->tokens()->delete();

            return response()->json([
                'message' => 'This account is suspended.',
                'code' => 'account_suspended',
                'suspended_until' => $user->suspended_until?->toIso8601String(),
                'suspension_reason' => \App\Support\Suspension::describe($user->suspension_reason, $user->suspension_note),
            ], 403);
        }

        return $next($request);
    }
}
