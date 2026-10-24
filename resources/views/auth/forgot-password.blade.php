
<x-guest-layout>

    <div id="forgot-container">

        <div class="forgot-card">

            <!-- =========================================
                 LOGO / BRAND
                 ========================================= -->
            <div class="auth-brand">
                LEARN LOOP
            </div>

 
            <!-- =========================================
                 HEADER
                 ========================================= -->
            <div class="auth-header">

                <span class="auth-label">
                    ACCOUNT RECOVERY
                </span>

                <h1>
                    FORGOT PASSWORD
                </h1>

                <p>
                    Forgot your password? No problem. Enter your
                    email address and we will email you a 6-digit
                    code to set a new one.
                </p>

            </div>


            <!-- =========================================
                 SESSION STATUS
                 ========================================= -->
            <x-auth-session-status
                class="auth-status"
                :status="session('status')"
            />


            <!-- =========================================
                 RESET PASSWORD FORM
                 ========================================= -->
            <form
                method="POST"
                action="{{ route('password.email') }}"
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


                <!-- RESET BUTTON -->
                <button
                    type="submit"
                    class="auth-submit"
                >
                    Send Code
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
                    ← Back to Login
                </a>

            </div>

        </div>

    </div>

</x-guest-layout>

