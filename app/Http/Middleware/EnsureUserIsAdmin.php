<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsAdmin
{
   
public function handle(Request $request, Closure $next): Response
{
    $user = $request->user();

    if (! $user || $user->role !== 'admin') {
        abort(403, 'Admins only.');
    }

    if ($user->status !== 'active') {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['email' => 'This account is suspended.']);
    }

    return $next($request);
}
}