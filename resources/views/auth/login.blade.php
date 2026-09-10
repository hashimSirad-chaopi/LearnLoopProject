
<x-guest-layout>

    <div id="container">

        <!-- =========================================
             LOGO / BRAND
             ========================================= -->
        <div class="auth-brand">
            LEARN LOOP
        </div>


        <!-- =========================================
             LOGIN HEADER
             ========================================= -->
        <div class="auth-header">

            <span class="auth-label">
                ADMIN PANEL
            </span>

            <h1>
                LOG IN
            </h1>

            <p>
                Sign in to access the LearnLoop administration panel.
            </p>

        </div>


        <!-- =========================================
             SESSION STATUS
             ========================================= -->
        @if (session('status'))

            <div class="auth-status">
                {{ session('status') }}
            </div>

        @endif


        <!-- =========================================
             LOGIN FORM
             ========================================= -->
        <form
            method="POST"
            action="{{ route('login') }}"
            class="auth-form"
        >

            @csrf


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
                    autofocus
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

                <div class="password-wrapper">

                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password"
                    >

                    <button
                        type="button"
                        class="password-toggle"
                        onclick="togglePassword()"
                        aria-label="Show password"
                    >

                        <svg
                            id="eyeIcon"
                            xmlns="http://www.w3.org/2000/svg"
                            width="20"
                            height="20"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >

                            <path
                                d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"
                            ></path>

                            <circle
                                cx="12"
                                cy="12"
                                r="3"
                            ></circle>

                        </svg>

                    </button>

                </div>


                @error('password')

                    <p class="auth-error">
                        {{ $message }}
                    </p>

                @enderror

            </div>


            <!-- LOGIN BUTTON -->
            <button
                type="submit"
                class="auth-submit"
            >
                Login
            </button>

        </form>


        <!-- =========================================
             FORGOT PASSWORD
             ========================================= -->
        <div class="auth-footer">

            <a
                href="{{ route('password.request') }}"
                class="forgot-link"
            >
                Forgot Password?
            </a>

        </div>

    </div>


    <!-- =========================================
         PASSWORD TOGGLE
         ========================================= -->
    <script>

        function togglePassword() {

            const input = document.getElementById('password');
            const icon = document.getElementById('eyeIcon');

            if (input.type === 'password') {

                input.type = 'text';

                icon.style.opacity = '0.6';

            } else {

                input.type = 'password';

                icon.style.opacity = '1';

            }

        }

    </script>

</x-guest-layout>

