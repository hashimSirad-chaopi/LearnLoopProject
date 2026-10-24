<x-guest-layout>

    <div id="forgot-container">

        <div class="forgot-card">

            <div class="auth-brand">LEARN LOOP</div>

            <div class="auth-header">
                <span class="auth-label">ACCOUNT RECOVERY</span>
                <h1>ENTER YOUR CODE</h1>
                <p>
                    We emailed a 6-digit code to <strong>{{ $maskedEmail }}</strong>.
                    Type it below and choose a new password.
                </p>
            </div>

            <x-auth-session-status class="auth-status" :status="session('status')" />

            <form method="POST" action="{{ route('password.store') }}" class="auth-form">
                @csrf

                <div class="auth-field">
                    <label for="code">6-digit code</label>
                    <input
                        id="code"
                        type="text"
                        name="code"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        maxlength="6"
                        placeholder="123456"
                        value="{{ old('code') }}"
                        required
                        autofocus
                    >
                    @error('code')
                        <p class="auth-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="auth-field">
                    <label for="password">New password</label>
                    <input id="password" type="password" name="password" placeholder="At least 8 characters" required autocomplete="new-password">
                    @error('password')
                        <p class="auth-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="auth-field">
                    <label for="password_confirmation">Confirm new password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Type it again" required autocomplete="new-password">
                </div>

                <button type="submit" class="auth-submit">Change Password</button>
            </form>

            <form method="POST" action="{{ route('password.resend') }}" style="margin-top:14px; text-align:center;">
                @csrf
                <button type="submit" class="forgot-link" style="background:none; border:0; cursor:pointer;">
                    Did not get it? Send a new code
                </button>
            </form>

            <div class="auth-footer">
                <a href="{{ route('login') }}" class="forgot-link">← Back to Login</a>
            </div>

        </div>

    </div>

</x-guest-layout>
