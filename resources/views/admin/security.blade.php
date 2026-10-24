<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - Security</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div class="admin-container">

    @include('partials.admin-sidebar')

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <span class="header-label">ADMIN PANEL</span>
                <h1>Security</h1>
                <p>Every sign-in needs your password and a code sent to your email.</p>
            </div>
        </header>

        <section class="users-page">
            <div class="ll-scroll">
                <div class="ll-page">
                    @include('partials.flash')

                    <div class="sec-grid">

                        {{-- ===== Status ===== --}}
                        <div class="ll-card">
                            <div class="ll-row between">
                                <h3>Email sign-in code</h3>
                                <span class="ll-badge {{ $enabled ? 'completed' : 'pending' }}">{{ $enabled ? 'Always on' : 'Off' }}</span>
                            </div>
                            @if($enabled)
                                <div class="sec-ok">
                                    After your password, a 6-digit code is emailed to <strong>{{ $maskedEmail }}</strong>. It works once and expires in {{ \App\Support\EmailOtp::TTL_MINUTES }} minutes. After 5 wrong tries it is cancelled.
                                </div>
                                <p class="ll-muted">Make sure you can read that mailbox. Forgot your password? Use <em>Forgot password</em> on the login page: it also sends a code to this email.</p>
                            @else
                                <div class="sec-warn">The email code is switched off in the server settings (<code>learnloop.admin_email_otp</code>). Anyone with your password can sign in.</div>
                            @endif
                        </div>

                        {{-- ===== Trusted browsers ===== --}}
                        <div class="ll-card">
                            <div class="ll-row between">
                                <h3>Trusted browsers</h3>
                                @if($devices->count() > 1)
                                    <form method="POST" action="{{ route('admin.security.devices.clear') }}" onsubmit="return confirm('Stop trusting all browsers?');">
                                        @csrf
                                        <button type="submit" class="table-btn">Remove all</button>
                                    </form>
                                @endif
                            </div>
                            <p class="ll-muted">Browsers where you ticked “Trust this browser” skip the code for {{ \App\Support\AdminDevices::DAYS }} days. Remove any you do not recognise.</p>

                            @forelse($devices as $device)
                                <div class="ll-row between" style="padding:10px 0; border-top:1px solid rgba(0,0,0,.07);">
                                    <div>
                                        <strong>{{ $device->label ?? 'Browser' }}</strong>
                                        @if($currentDevice === $device->id)<span class="ll-badge completed">This browser</span>@endif
                                        <div class="ll-muted" style="font-size:12.5px;">
                                            {{ $device->ip }} &middot; last used {{ \Illuminate\Support\Carbon::parse($device->last_used_at)->diffForHumans() }}
                                            &middot; trusted until {{ \Illuminate\Support\Carbon::parse($device->expires_at)->format('M j') }}
                                        </div>
                                    </div>
                                    <form method="POST" action="{{ route('admin.security.devices.remove', $device->id) }}">
                                        @csrf
                                        <button type="submit" class="delete-btn">Remove</button>
                                    </form>
                                </div>
                            @empty
                                <p class="ll-muted">No trusted browsers. You will get a code every time you sign in.</p>
                            @endforelse
                        </div>

                        {{-- ===== Recent events ===== --}}
                        <div class="ll-card">
                            <h3>Recent sign-in activity</h3>
                            @forelse($events as $event)
                                <div style="padding:8px 0; border-top:1px solid rgba(0,0,0,.07); font-size:13.5px;">
                                    {{ $event->description }}
                                    <span class="ll-muted">&middot; {{ $event->created_at->diffForHumans() }}</span>
                                </div>
                            @empty
                                <p class="ll-muted">Nothing yet.</p>
                            @endforelse
                            <p class="ll-muted" style="margin-top:10px;">Everything else you do is in <a href="{{ route('admin.activity') }}">Activity</a>.</p>
                        </div>

                        {{-- ===== What is protected ===== --}}
                        <div class="ll-card">
                            <h3>Other protection already in place</h3>
                            <ul class="ll-muted" style="padding-left:18px; line-height:1.8;">
                                <li>Admin login locks for a minute after 5 wrong tries.</li>
                                <li>Only active admin accounts can sign in to this panel.</li>
                                <li>Changing a password with an email code signs out every phone and trusted browser.</li>
                                <li>App login and sign-up are rate limited.</li>
                            </ul>
                        </div>

                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

</body>
</html>
