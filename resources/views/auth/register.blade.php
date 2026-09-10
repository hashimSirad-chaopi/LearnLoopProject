```blade
<x-guest-layout>

    <div id="container">

        <!-- =========================================
             LOGO / BRAND
             ========================================= -->
        <div class="auth-brand">
            LEARN LOOP
        </div>


        <!-- =========================================
             REGISTER HEADER
             ========================================= -->
        <div class="auth-header">

            <span class="auth-label">
                ADMIN PANEL
            </span>

            <h1>
                CREATE ACCOUNT
            </h1>

            <p>
                Create a new LearnLoop account.
            </p>

        </div>


        <!-- =========================================
             REGISTER FORM
             ========================================= -->
        <form
            method="POST"
            action="{{ route('register') }}"
            class="auth-form"
        >

            @csrf


            <!-- NAME -->
            <div class="auth-field">

                <label for="name">
                    Name
                </label>

                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    placeholder="Enter your name"
                    required
                    autofocus
                    autocomplete="name"
                >

                @error('name')

                    <p class="auth-error">
                        {{ $message }}
                    </p>

                @enderror

            </div>


            <!-- EMAIL -->
            <div class="auth-field">

                <label for="email">
                    Email
                </label>

                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="Enter your email"
                    required
                    autocomplete="username"
                >

                @error('email')

                    <p class="auth-error">
                        {{ $message }}
                    </p>

                @enderror

            </div>


            <!-- PASSWORD -->
            <div class="auth-field">

                <label for="password">
                    Password
                </label>

                <input
                    id="password"
                    type="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                    autocomplete="new-password"
                >

                @error('password')

                    <p class="auth-error">
                        {{ $message }}
                    </p>

                @enderror

            </div>


            <!-- CONFIRM PASSWORD -->
            <div class="auth-field">

                <label for="password_confirmation">
                    Confirm Password
                </label>

                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    placeholder="Confirm your password"
                    required
                    autocomplete="new-password"
                >

                @error('password_confirmation')

                    <p class="auth-error">
                        {{ $message }}
                    </p>

                @enderror

            </div>


            <!-- REGISTER BUTTON -->
            <button
                type="submit"
                class="auth-submit"
            >
                Register
            </button>

        </form>


        <!-- =========================================
             BACK TO LOGIN
             ========================================= -->
        <div class="auth-footer">

            <a
                href="{{ route('login') }}"
                class="forgot-link"
            >
                Already have an account? Log In
            </a>

        </div>

    </div>

</x-guest-layout>
```
