<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Verify sign-in &middot; {{ config('app.name', 'LearnLoop') }}</title>

    @vite(['resources/css/app.css'])
</head>
<body>

    <main id="container">

        <div class="auth-brand">LEARN LOOP</div>

        <div class="auth-header">
            <span class="auth-label">Admin Panel</span>
            <h1>Check your email</h1>
            <p>We sent a 6-digit code to <strong>{{ $maskedEmail }}</strong>. It works for {{ \App\Support\EmailOtp::TTL_MINUTES }} minutes.</p>
        </div>

        @if(session('status'))
            <p class="auth-status" role="status">{{ session('status') }}</p>
        @endif
        @if($sendError)
            <p class="auth-error" role="alert">{{ $sendError }}</p>
        @endif

        <form method="POST" action="{{ route('admin.verify.check') }}" class="auth-form">
            @csrf

            <div class="auth-field">
                <label for="code">Code</label>
                <input
                    id="code"
                    type="text"
                    name="code"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    maxlength="6"
                    placeholder="123456"
                    class="{{ $errors->has('code') ? 'is-invalid' : '' }}"
                    required
                    autofocus
                >
                @error('code')
                    <p class="auth-error" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <label style="display:flex; gap:8px; align-items:center; font-size:13.5px; margin:0 0 14px;">
                <input type="checkbox" name="trust" value="1" style="width:auto; margin:0;">
                Trust this browser for {{ \App\Support\AdminDevices::DAYS }} days (do not ask again here)
            </label>

            <button type="submit" class="auth-submit">Verify</button>
        </form>

        <form method="POST" action="{{ route('admin.verify.resend') }}" style="margin-top:14px; text-align:center;">
            @csrf
            <button type="submit" class="forgot-link" style="background:none; border:0; cursor:pointer;">
                Did not get it? Send a new code{{ $wait > 0 ? " (wait {$wait}s)" : '' }}
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" style="margin-top:8px; text-align:center;">
            @csrf
            <button type="submit" class="forgot-link" style="background:none; border:0; cursor:pointer;">Sign out instead</button>
        </form>

    </main>
</body>
</html>
