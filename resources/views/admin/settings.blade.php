<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - Settings</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

@php
    $tabs = [
        'account' => 'Account',
        'password' => 'Password',
        'security' => 'Security',
        'notifications' => 'Notifications',
        'quizzes' => 'Quizzes',
    ];
    $emailChangeOpen = $errors->has('email_confirmation') || $errors->has('current_password') && $tab === 'account';
@endphp

<div class="admin-container">

    @include('partials.admin-sidebar')

    <main class="main-content">

        <header class="top-header">
            <div class="header-left">
                <span class="header-label">ADMIN PANEL</span>
                <h1>Settings</h1>
                <p>Your account, password, sign-in security, email alerts and quiz limits.</p>
            </div>
        </header>

        <section class="settings-page">
            <div class="settings-scroll-area">

                @if(session('success'))
                    <div class="ll-flash st-flash">{{ session('success') }}</div>
                @endif
                @if($errors->has('image'))
                    <div class="ll-flash error st-flash">{{ $errors->first('image') }}</div>
                @endif
                @if($errors->has('notifications'))
                    <div class="ll-flash error st-flash">{{ $errors->first('notifications') }}</div>
                @endif
                @if($errors->has('quizzes'))
                    <div class="ll-flash error st-flash">{{ $errors->first('quizzes') }}</div>
                @endif
                @if(collect($errors->keys())->contains(fn ($k) => str_starts_with($k, 'attempts.')))
                    <div class="ll-flash error st-flash">{{ $errors->first() }}</div>
                @endif

                <div class="st-layout">

                    {{-- ============ Tabs ============ --}}
                    <nav class="st-tabs" aria-label="Settings sections">
                        @foreach($tabs as $key => $label)
                            <a href="{{ route('admin.settings', ['tab' => $key]) }}"
                               class="st-tab {{ $tab === $key ? 'active' : '' }}"
                               data-tab="{{ $key }}">
                                {{ $label }}
                                @if($key === 'security' && ! $otpEnabled)
                                    <span class="st-dot warn" title="The email code is switched off"></span>
                                @endif
                            </a>
                        @endforeach
                    </nav>

                    <div class="st-panels">

                        {{-- ============ ACCOUNT ============ --}}
                        <div class="st-panel" data-panel="account" @if($tab !== 'account') hidden @endif>

                            <div class="settings-card st-me">
                                {{-- Banner --}}
                                <div class="st-banner" @if($me->banner_url) style="background-image:url('{{ $me->banner_url }}')" @endif>
                                    <div class="st-banner-actions">
                                        <form method="POST" action="{{ route('admin.settings.picture', 'banner') }}" enctype="multipart/form-data">
                                            @csrf
                                            <input type="hidden" name="tab" value="account">
                                            <label class="st-pick">
                                                {{ $me->banner_path ? 'Change banner' : 'Add banner' }}
                                                <input type="file" name="image" accept="image/jpeg,image/png,image/webp" onchange="this.form.submit()">
                                            </label>
                                        </form>
                                        @if($me->banner_path)
                                            <form method="POST" action="{{ route('admin.settings.picture.remove', 'banner') }}" onsubmit="return confirm('Remove your banner?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="st-pick danger">Remove</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>

                                <div class="st-me-body">
                                    @include('partials.avatar', ['user' => $me, 'class' => 'st-avatar', 'letters' => 1])
                                    <div class="st-me-text">
                                        <h2>{{ $me->name }}</h2>
                                        <p>{{ $me->email }}</p>
                                        <div class="st-me-meta">
                                            <span class="ll-badge completed">Administrator</span>
                                            <span>User since {{ $me->created_at?->format('M j, Y') }}</span>
                                        </div>
                                    </div>
                                    <div class="st-avatar-actions">
                                        <form method="POST" action="{{ route('admin.settings.picture', 'avatar') }}" enctype="multipart/form-data">
                                            @csrf
                                            <input type="hidden" name="tab" value="account">
                                            <label class="st-pick dark">
                                                {{ $me->avatar_path ? 'Change picture' : 'Add picture' }}
                                                <input type="file" name="image" accept="image/jpeg,image/png,image/webp" onchange="this.form.submit()">
                                            </label>
                                        </form>
                                        @if($me->avatar_path)
                                            <form method="POST" action="{{ route('admin.settings.picture.remove', 'avatar') }}" onsubmit="return confirm('Remove your profile picture?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="st-pick dark danger">Remove</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                                <p class="st-hint" style="margin:10px 0 0;">JPG, PNG or WebP, up to 5 MB. A wide picture works best for the banner.</p>
                            </div>

                            <div class="settings-card">
                                <div class="settings-card-header">
                                    <span class="section-label">ACCOUNT</span>
                                    <h2>Your details</h2>
                                    <p>The name shown in the panel and the email address that receives your sign-in codes.</p>
                                </div>

                                <form class="st-form" method="POST" action="{{ route('admin.settings.account') }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="tab" value="account">

                                    <div class="st-field">
                                        <label for="st-name">Name</label>
                                        <input id="st-name" name="name" type="text" maxlength="255" autocomplete="name"
                                               value="{{ old('name', $me->name) }}" required>
                                        @error('name')<span class="st-error">{{ $message }}</span>@enderror
                                    </div>

                                    <div class="st-field">
                                        <label for="st-email">Email</label>
                                        <input id="st-email" name="email" type="email" maxlength="255" autocomplete="email"
                                               value="{{ old('email', $me->email) }}" data-original="{{ $me->email }}" required>
                                        @error('email')<span class="st-error">{{ $message }}</span>@enderror
                                    </div>

                                    <div class="st-reveal" id="st-email-change" @if(! $emailChangeOpen) hidden @endif>
                                        <div class="settings-warning">
                                            Sign-in codes are emailed to this address. Check it carefully: a wrong address would lock you out.
                                        </div>

                                        <div class="st-field">
                                            <label for="st-email2">Type the new email again</label>
                                            <input id="st-email2" name="email_confirmation" type="email" autocomplete="off"
                                                   value="{{ old('email_confirmation') }}">
                                            @error('email_confirmation')<span class="st-error">{{ $message }}</span>@enderror
                                        </div>

                                        <div class="st-field">
                                            <label for="st-email-pw">Your password</label>
                                            <input id="st-email-pw" name="current_password" type="password" autocomplete="current-password"
                                                   placeholder="Needed to change the email">
                                            @if($tab === 'account')
                                                @error('current_password')<span class="st-error">{{ $message }}</span>@enderror
                                            @endif
                                        </div>
                                    </div>

                                    <div class="settings-actions">
                                        <button type="submit" class="settings-save-btn">Save changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- ============ PASSWORD ============ --}}
                        <div class="st-panel" data-panel="password" @if($tab !== 'password') hidden @endif>
                            <div class="settings-card">
                                <div class="settings-card-header">
                                    <span class="section-label">PASSWORD</span>
                                    <h2>Change password</h2>
                                    <p>
                                        @if($lastPasswordChange)
                                            Last changed {{ $lastPasswordChange->diffForHumans() }}.
                                        @else
                                            Use a password you do not use anywhere else.
                                        @endif
                                    </p>
                                </div>

                                <form class="st-form" method="POST" action="{{ route('admin.settings.password') }}" autocomplete="off">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="tab" value="password">

                                    <div class="st-field">
                                        <label for="st-current">Current password</label>
                                        <div class="st-pw">
                                            <input id="st-current" name="current_password" type="password" autocomplete="current-password" required>
                                            <button type="button" class="st-eye" data-toggle-pw="st-current">Show</button>
                                        </div>
                                        @if($tab === 'password')
                                            @error('current_password')<span class="st-error">{{ $message }}</span>@enderror
                                        @endif
                                    </div>

                                    <div class="st-field">
                                        <label for="st-new">New password</label>
                                        <div class="st-pw">
                                            <input id="st-new" name="password" type="password" autocomplete="new-password" minlength="8" required>
                                            <button type="button" class="st-eye" data-toggle-pw="st-new">Show</button>
                                        </div>
                                        <div class="st-meter" aria-hidden="true"><i id="st-meter-bar"></i></div>
                                        <span class="st-hint" id="st-meter-text">At least 8 characters. Longer is stronger.</span>
                                        @error('password')<span class="st-error">{{ $message }}</span>@enderror
                                    </div>

                                    <div class="st-field">
                                        <label for="st-confirm">Confirm new password</label>
                                        <div class="st-pw">
                                            <input id="st-confirm" name="password_confirmation" type="password" autocomplete="new-password" required>
                                            <button type="button" class="st-eye" data-toggle-pw="st-confirm">Show</button>
                                        </div>
                                        <span class="st-hint" id="st-match-text"></span>
                                    </div>

                                    <div class="settings-warning">
                                        After a change, phones and trusted browsers are signed out and the next sign-in asks for an email code.
                                    </div>

                                    <div class="settings-actions" style="margin-top:14px;">
                                        <button type="submit" class="settings-save-btn">Update password</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- ============ SECURITY ============ --}}
                        <div class="st-panel" data-panel="security" @if($tab !== 'security') hidden @endif>

                            <div class="settings-card">
                                <div class="st-between">
                                    <div class="settings-card-header" style="margin-bottom:0;">
                                        <span class="section-label">SECURITY</span>
                                        <h2>Email sign-in code</h2>
                                    </div>
                                    <span class="ll-badge {{ $otpEnabled ? 'completed' : 'pending' }}">{{ $otpEnabled ? 'Always on' : 'Off' }}</span>
                                </div>

                                @if($otpEnabled)
                                    <div class="sec-ok" style="margin-top:14px;">
                                        After your password, a 6-digit code is emailed to <strong>{{ $maskedEmail }}</strong>.
                                        It works once and expires in {{ \App\Support\EmailOtp::TTL_MINUTES }} minutes. After 5 wrong tries it is cancelled.
                                    </div>
                                    <p class="ll-muted" style="margin-top:10px;">
                                        Make sure you can read that mailbox. Forgot your password? Use <em>Forgot password</em> on the login page: it also sends a code to this email.
                                    </p>
                                @else
                                    <div class="sec-warn" style="margin-top:14px;">
                                        The email code is switched off in the server settings (<code>learnloop.admin_email_otp</code>). Anyone with your password can sign in.
                                    </div>
                                @endif
                            </div>

                            <div class="settings-card">
                                <div class="st-between">
                                    <div class="settings-card-header" style="margin-bottom:0;">
                                        <span class="section-label">DEVICES</span>
                                        <h2>Trusted browsers</h2>
                                    </div>
                                    @if($devices->count() > 1)
                                        <form method="POST" action="{{ route('admin.security.devices.clear') }}" onsubmit="return confirm('Stop trusting all browsers?');">
                                            @csrf
                                            <button type="submit" class="table-btn">Remove all</button>
                                        </form>
                                    @endif
                                </div>
                                <p class="ll-muted" style="margin:8px 0 6px;">
                                    Browsers where you ticked “Trust this browser” skip the code for {{ \App\Support\AdminDevices::DAYS }} days. Remove any you do not recognise.
                                </p>

                                @forelse($devices as $device)
                                    <div class="st-device">
                                        <div>
                                            <strong>{{ $device->label ?? 'Browser' }}</strong>
                                            @if($currentDevice === $device->id)<span class="ll-badge completed">This browser</span>@endif
                                            <div class="ll-muted">
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
                                    <p class="ll-muted st-empty">No trusted browsers. You will get a code every time you sign in.</p>
                                @endforelse
                            </div>

                            <div class="settings-card">
                                <div class="settings-card-header">
                                    <span class="section-label">HISTORY</span>
                                    <h2>Recent sign-in activity</h2>
                                </div>

                                @forelse($events as $event)
                                    <div class="st-event">
                                        <span class="st-dot {{ $event->action === 'admin.verify_failed' ? 'bad' : 'ok' }}"></span>
                                        <span>{{ $event->description }}</span>
                                        <span class="ll-muted">{{ $event->created_at->diffForHumans() }}</span>
                                    </div>
                                @empty
                                    <p class="ll-muted st-empty">Nothing yet.</p>
                                @endforelse

                                <p class="ll-muted" style="margin-top:12px;">Everything else you do is in <a href="{{ route('admin.activity') }}">Activity Log</a>.</p>
                            </div>

                            <div class="settings-card">
                                <div class="settings-card-header">
                                    <span class="section-label">PROTECTION</span>
                                    <h2>Already in place</h2>
                                </div>
                                <ul class="st-list">
                                    <li>Admin login locks for a minute after 5 wrong tries.</li>
                                    <li>Only active admin accounts can sign in to this panel.</li>
                                    <li>Changing a password signs out every phone and trusted browser.</li>
                                    <li>App login and sign-up are rate limited.</li>
                                </ul>
                            </div>
                        </div>

                        {{-- ============ NOTIFICATIONS ============ --}}
                        <div class="st-panel" data-panel="notifications" @if($tab !== 'notifications') hidden @endif>
                            <div class="settings-card">
                                <div class="settings-card-header">
                                    <span class="section-label">ALERTS</span>
                                    <h2>Email alerts</h2>
                                    <p>Work that needs you always shows in the sidebar and on the <a href="{{ route('admin.notifications') }}">Notifications</a> page. Switch on email to hear about it without opening the panel.</p>
                                </div>

                                @unless($mailSends)
                                    <div class="sec-warn" style="margin-bottom:6px;">
                                        This server is not set up to send email yet (mail is set to <code>{{ config('mail.default') }}</code>), so these emails are only written to the log.
                                        Set <code>MAIL_MAILER</code> and the mail settings in <code>.env</code> to send them for real.
                                    </div>
                                @endunless

                                <form method="POST" action="{{ route('admin.settings.notifications') }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="tab" value="notifications">

                                    <div class="settings-option">
                                        <div>
                                            <h3>New reports</h3>
                                            <p>Email me when a user reports a listing or another user.</p>
                                        </div>
                                        <label class="toggle">
                                            <input type="hidden" name="email_report_alerts" value="0">
                                            <input type="checkbox" name="email_report_alerts" value="1" {{ $prefs['email_report_alerts'] ? 'checked' : '' }}>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>

                                    <div class="settings-option">
                                        <div>
                                            <h3>Account recovery requests</h3>
                                            <p>Email me when a suspended user asks to be reactivated.</p>
                                        </div>
                                        <label class="toggle">
                                            <input type="hidden" name="email_appeal_alerts" value="0">
                                            <input type="checkbox" name="email_appeal_alerts" value="1" {{ $prefs['email_appeal_alerts'] ? 'checked' : '' }}>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>

                                    <div class="settings-actions" style="margin-top:6px;">
                                        <button type="submit" class="settings-save-btn">Save preferences</button>
                                    </div>
                                </form>
                            </div>
                        </div>


                        {{-- ============ QUIZZES ============ --}}
                        <div class="st-panel" data-panel="quizzes" @if($tab !== 'quizzes') hidden @endif>
                            <div class="settings-card">
                                <div class="settings-card-header">
                                    <span class="section-label">QUIZZES</span>
                                    <h2>Daily quiz attempts</h2>
                                    <p>Limits how many times a user can take the quiz of one of <strong>your</strong> lessons (Continue Learning) in a day, set separately for each level. It does not apply to exchanges, or to the lessons users write themselves (Profile &rarr; My Lessons).</p>
                                </div>

                                @unless($quiz['ready'])
                                    <div class="sec-warn" style="margin-bottom:6px;">
                                        The database needs one update before this works. Run <code>php artisan migrate</code>, then reload this page.
                                    </div>
                                @endunless

                                <form method="POST" action="{{ route('admin.settings.quizzes') }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="tab" value="quizzes">

                                    <div class="settings-option">
                                        <div>
                                            <h3>Limit quiz attempts</h3>
                                            <p>Switch off to let users retake these quizzes as often as they like.</p>
                                        </div>
                                        <label class="toggle">
                                            <input type="hidden" name="enabled" value="0">
                                            <input type="checkbox" name="enabled" value="1" id="quiz-limit-enabled" {{ $quiz['enabled'] ? 'checked' : '' }}>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>

                                    @foreach(\App\Support\QuizAttempts::LABELS as $level => $label)
                                        <div class="settings-option">
                                            <div>
                                                <h3>{{ $label }}</h3>
                                                <p>Tries per lesson, per day{{ $loop->first ? ' (the count starts again at midnight, '.$quiz['timezone'].')' : '' }}.</p>
                                            </div>
                                            <input type="number" name="attempts[{{ $level }}]" class="quiz-limit-number"
                                                   min="1" max="{{ \App\Support\QuizAttempts::MAX_LIMIT }}" step="1" inputmode="numeric"
                                                   value="{{ old('attempts.'.$level, $quiz['limits'][$level]) }}"
                                                   style="width:90px;padding:8px 10px;border:1px solid #d7ddd9;border-radius:8px;font:inherit;"
                                                   {{ $quiz['enabled'] ? '' : 'disabled' }}>
                                        </div>
                                    @endforeach

                                    <div class="settings-actions" style="margin-top:6px;">
                                        <button type="submit" class="settings-save-btn">Save quiz settings</button>
                                    </div>
                                </form>
                                <script>
                                    (function () {
                                        var box = document.getElementById('quiz-limit-enabled');
                                        var nums = document.querySelectorAll('.quiz-limit-number');
                                        if (!box || !nums.length) return;
                                        // A disabled input is not submitted, so keep the saved numbers by re-enabling them on submit.
                                        box.addEventListener('change', function () { nums.forEach(function (n) { n.disabled = !box.checked; }); });
                                        box.form.addEventListener('submit', function () { nums.forEach(function (n) { n.disabled = false; }); });
                                    })();
                                </script>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </section>
    </main>
</div>

<script>
(function () {
    // ---- Tabs: switch without reloading, keep the address in step so refresh and links work ----
    var tabs = document.querySelectorAll('.st-tab');
    var panels = document.querySelectorAll('.st-panel');

    function show(name, push) {
        tabs.forEach(function (t) { t.classList.toggle('active', t.dataset.tab === name); });
        panels.forEach(function (p) { p.hidden = p.dataset.panel !== name; });
        if (push && window.history && history.replaceState) {
            history.replaceState(null, '', '{{ route('admin.settings') }}?tab=' + name);
        }
    }
    tabs.forEach(function (t) {
        t.addEventListener('click', function (e) { e.preventDefault(); show(t.dataset.tab, true); });
    });

    // ---- Email: ask for the extra confirmation only when the address is being changed ----
    var email = document.getElementById('st-email');
    var reveal = document.getElementById('st-email-change');
    if (email && reveal) {
        email.addEventListener('input', function () {
            var changed = email.value.trim().toLowerCase() !== email.dataset.original.toLowerCase();
            reveal.hidden = !changed;
        });
    }

    // ---- Password: show / hide ----
    document.querySelectorAll('[data-toggle-pw]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.dataset.togglePw);
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.textContent = show ? 'Hide' : 'Show';
        });
    });

    // ---- Password: strength hint and match check ----
    var pw = document.getElementById('st-new');
    var confirm = document.getElementById('st-confirm');
    var bar = document.getElementById('st-meter-bar');
    var text = document.getElementById('st-meter-text');
    var match = document.getElementById('st-match-text');

    function score(v) {
        var s = 0;
        if (v.length >= 8) s++;
        if (v.length >= 12) s++;
        if (/[a-z]/.test(v) && /[A-Z]/.test(v)) s++;
        if (/\d/.test(v)) s++;
        if (/[^A-Za-z0-9]/.test(v)) s++;
        return s;
    }
    var labels = ['Too short', 'Weak', 'Fair', 'Good', 'Strong', 'Very strong'];
    var colors = ['#B14D48', '#B14D48', '#B78334', '#8FD64C', '#4E8A43', '#4E8A43'];

    function update() {
        if (pw && bar) {
            var v = pw.value, s = v.length ? score(v) : 0;
            bar.style.width = (v.length ? Math.max(12, s * 20) : 0) + '%';
            bar.style.background = colors[s];
            text.textContent = v.length ? labels[s] : 'At least 8 characters. Longer is stronger.';
        }
        if (confirm && match) {
            if (!confirm.value.length) { match.textContent = ''; match.className = 'st-hint'; }
            else if (confirm.value === pw.value) { match.textContent = 'Passwords match.'; match.className = 'st-hint ok'; }
            else { match.textContent = 'Passwords do not match yet.'; match.className = 'st-hint bad'; }
        }
    }
    if (pw) pw.addEventListener('input', update);
    if (confirm) confirm.addEventListener('input', update);
})();
</script>

</body>
</html>
