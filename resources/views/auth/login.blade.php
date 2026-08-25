<x-guest-layout>
    <div id="container">
        <h1>LEARN LOOP</h1>
        <h3>LOG IN</h3>

        @if (session('status'))
            <div>{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <input type="email" name="email" placeholder="Email" value="{{ old('email') }}" required autofocus>
            @error('email') <p style="color:red;font-size:13px;">{{ $message }}</p> @enderror

            <input type="password" name="password" placeholder="Password" required>
            @error('password') <p style="color:red;font-size:13px;">{{ $message }}</p> @enderror

            <button type="submit">Login</button>
        </form>

        <br>
        <a href="{{ route('register') }}"><button type="button">Register</button></a>

        <p class="forgot">
            <a href="{{ route('password.request') }}"><span>Forgot Password?</span></a>
        </p>
    </div>
</x-guest-layout>