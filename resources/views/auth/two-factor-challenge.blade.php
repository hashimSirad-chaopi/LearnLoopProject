<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Two-step verification &middot; {{ config('app.name', 'LearnLoop') }}</title>

    @vite(['resources/css/app.css'])
</head>
<body>

    <main id="container">

        <div class="auth-brand">LEARN LOOP</div>

        <div class="auth-header">
            <span class="auth-label">Admin Panel</span>
            <h1>Two-step check</h1>
            <p>Open your authenticator app and enter the 6-digit code for LearnLoop.</p>
        </div>

        <form method="POST" action="{{ route('admin.two-factor.verify') }}" class="auth-form">
            @csrf

            <div class="auth-field">
                <label for="code">Code</label>
                <input
                    id="code"
                    type="text"
                    name="code"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    placeholder="123456"
                    class="{{ $errors->has('code') ? 'is-invalid' : '' }}"
                    required
                    autofocus
                >
                @error('code')
                    <p class="auth-error" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <p class="auth-hint" style="font-size:12.5px; opacity:.75; margin:0 0 14px;">
                Lost your phone? Type one of your recovery codes instead (like <em>k3f9a-7d2xq</em>). Each works once.
            </p>

            <button type="submit" class="auth-submit">Verify</button>
        </form>

        <form method="POST" action="{{ route('logout') }}" style="margin-top:14px; text-align:center;">
            @csrf
            <button type="submit" class="forgot-link" style="background:none; border:0; cursor:pointer;">Sign out instead</button>
        </form>

    </main>
</body>
</html>
