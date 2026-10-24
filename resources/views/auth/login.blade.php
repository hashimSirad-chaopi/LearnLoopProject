<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Log In &middot; {{ config('app.name', 'LearnLoop') }}</title>

    @vite(['resources/css/app.css'])
</head>
<body>

    <main id="container">

        <div class="auth-brand">LEARN LOOP</div>

        <div class="auth-header">
            <span class="auth-label">Admin Panel</span>
            <h1>Log In</h1>
            <p>Sign in to access the LearnLoop administration panel.</p>
        </div>

        @if (session('status'))
            <div class="auth-status" role="status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="auth-form" id="loginForm">
            @csrf

            {{-- EMAIL --}}
            <div class="auth-field">
                <label for="email">Email</label>

                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="admin@example.com"
                    class="{{ $errors->has('email') ? 'is-invalid' : '' }}"
                    required
                    autofocus
                    autocomplete="username"
                    @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                >

                @error('email')
                    <p class="auth-error" id="email-error" role="alert">{{ $message }}</p>
                @enderror
            </div>

            {{-- PASSWORD --}}
            <div class="auth-field">
                <label for="password">Password</label>

                <div class="password-wrapper">
                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="Enter your password"
                        class="{{ $errors->has('password') ? 'is-invalid' : '' }}"
                        required
                        autocomplete="current-password"
                        @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                    >

                    <button
                        type="button"
                        class="password-toggle"
                        id="passwordToggle"
                        aria-label="Show password"
                        aria-pressed="false"
                    >
                        <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <svg class="icon-eye-off" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.5 18.5 0 0 1 5.06-5.94"/>
                            <path d="M9.9 4.24A9.1 9.1 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                            <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/>
                            <line x1="1" y1="1" x2="23" y2="23"/>
                        </svg>
                    </button>
                </div>

                @error('password')
                    <p class="auth-error" id="password-error" role="alert">{{ $message }}</p>
                @enderror
            </div>

            {{-- REMEMBER + FORGOT --}}
            <div class="auth-options">
                <label class="auth-remember" for="remember">
                    <input id="remember" type="checkbox" name="remember" value="1" @checked(old('remember'))>
                    <span>Remember me</span>
                </label>

                <a href="{{ route('password.request') }}" class="forgot-link">Forgot password?</a>
            </div>

            <button type="submit" class="auth-submit" id="loginSubmit">Log in</button>
        </form>

    </main>

    <script>
        (function () {
            var input  = document.getElementById('password');
            var toggle = document.getElementById('passwordToggle');
            var form   = document.getElementById('loginForm');
            var submit = document.getElementById('loginSubmit');

            toggle.addEventListener('click', function () {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                toggle.classList.toggle('is-visible', show);
                toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
                toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });

            // Stop double-submits (each failed try counts toward the rate limit).
            form.addEventListener('submit', function () {
                submit.disabled = true;
                submit.textContent = 'Signing in\u2026';
            });

            // Browser "back" can restore the page with the button still disabled.
            window.addEventListener('pageshow', function () {
                submit.disabled = false;
                submit.textContent = 'Log in';
            });
        })();
    </script>

</body>
</html>
