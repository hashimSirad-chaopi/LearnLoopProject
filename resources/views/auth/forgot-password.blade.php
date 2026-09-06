<x-guest-layout>

    <div id="forgot-container">

        <div class="forgot-card">

            <h1>LEARN LOOP</h1>

            <h2>FORGOT PASSWORD</h2>

            <p class="description">
                Forgot your password? No problem. Just enter your email
                address and we will send you a password reset link.
            </p>


            <!-- Session Status -->

            <x-auth-session-status
                class="mb-4"
                :status="session('status')"
            />


            <!-- Forgot Password Form -->

            <form method="POST" action="{{ route('password.email') }}">

                @csrf


                <!-- Email -->

                <div class="forgot-field">

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
                    >

                    <x-input-error
                        :messages="$errors->get('email')"
                        class="forgot-error"
                    />

                </div>


                <!-- Submit -->

                <button type="submit">
                    Email Password Reset Link
                </button>

            </form>


            <!-- Back to Login -->

            <a
                href="{{ route('login') }}"
                class="back-login"
            >
                ← Back to Login
            </a>

        </div>

    </div>

</x-guest-layout>