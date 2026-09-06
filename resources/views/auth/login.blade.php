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

          <div style="position:relative; width:100%; max-width:300px;">
    <input type="password" id="password" name="password" placeholder="Password" required style="width:100%; padding-right:36px;">
    <span onclick="togglePassword()" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer; user-select:none;">
        <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="gray" stroke-width="2">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
            <circle cx="12" cy="12" r="3"></circle>
        </svg>
    </span>
</div>
@error('password') <p style="color:red;font-size:13px;">{{ $message }}</p> @enderror

<script>
function togglePassword() {
    const input = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.setAttribute('stroke', '#333'); // darker = "showing"
    } else {
        input.type = 'password';
        icon.setAttribute('stroke', 'gray');
    }
}
</script>

            <button type="submit">Login</button>
        </form>

        <p class="forgot">
            <a href="{{ route('password.request') }}"><span>Forgot Password?</span></a>
        </p>
    </div>
</x-guest-layout>