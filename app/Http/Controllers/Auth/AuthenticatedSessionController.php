<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the admin login page.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle the login. LoginRequest::authenticate() has already confirmed the
     * account is an active admin by the time we get past it.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('admin.home'));
    }

    /**
     * Log out.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Logged out = offline right away.
        if ($request->user()) {
            \Illuminate\Support\Facades\DB::table('users')->where('id', $request->user()->id)->update(['last_seen_at' => now(), 'offline_since' => now()]);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
