<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - Edit User</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .eu-wrap { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 16px; align-items: start; }
        .eu-form { display: flex; flex-direction: column; gap: 16px; min-width: 0; }
        .eu-form .settings-field { margin: 0; }
        .eu-section h3 { margin: 0 0 4px; }
        .eu-section > p { margin: 0 0 14px; }
        .eu-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; }
        .eu-error { color: #B14D48; font-size: 13px; margin: 4px 0 0; }
        .eu-hint { color: var(--ll-muted, #69766E); font-size: 12.5px; margin: 4px 0 0; }
        .eu-pw { position: relative; }
        .eu-pw input { width: 100%; padding-right: 64px; box-sizing: border-box; }
        .eu-pw button { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); border: 0; background: none; color: var(--ll-muted, #69766E); font-weight: 800; font-size: 12px; cursor: pointer; }
        .eu-notice { margin-top: 12px; padding: 10px 14px; border-radius: 10px; background: #FFF4DD; border: 1px solid #E4C77A; color: #7A5E17; font-size: 13px; line-height: 1.5; }
        .eu-side { display: flex; flex-direction: column; gap: 16px; }
        .eu-person { display: flex; gap: 12px; align-items: center; }
        .eu-avatar { position: relative; width: 56px; height: 56px; border-radius: 14px; overflow: hidden; background: #B7FF72; flex-shrink: 0; }
        .eu-avatar span, .eu-avatar img { position: absolute; inset: 0; width: 100%; height: 100%; }
        .eu-avatar span { display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 22px; color: #16231C; }
        .eu-avatar img { object-fit: cover; }
        .eu-person strong { display: block; overflow-wrap: anywhere; }
        .eu-facts { margin: 14px 0 0; display: grid; gap: 8px; }
        .eu-facts div { display: flex; justify-content: space-between; gap: 10px; font-size: 13px; }
        .eu-facts dt { color: var(--ll-muted, #69766E); }
        .eu-facts dd { margin: 0; font-weight: 700; text-align: right; }
        @media (max-width: 1000px) { .eu-wrap { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="admin-container">

      @include('partials.admin-sidebar')

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <span class="header-label">ADMIN PANEL</span>
                <h1>Edit User</h1>
                <p>Update {{ $user->name }}'s name, email or password.</p>
            </div>
        </header>

        <section class="users-page">
            <div class="ll-scroll">
                <div class="ll-page">
                    @include('partials.flash')

                    <div class="eu-wrap">
                        <form class="eu-form" id="edit-user-form" method="POST" action="{{ route('admin.users.update', $user->id) }}" autocomplete="off">
                            @csrf
                            @method('PUT')

                            <div class="ll-card eu-section">
                                <h3>Account details</h3>
                                <p class="ll-muted">The name other members see, and the email they sign in with.</p>
                                <div class="eu-grid">
                                    <div class="settings-field">
                                        <label for="name">Name</label>
                                        <input id="name" name="name" type="text" maxlength="255" required value="{{ old('name', $user->name) }}">
                                        @error('name') <p class="eu-error">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="settings-field">
                                        <label for="email">Email</label>
                                        <input id="email" name="email" type="email" maxlength="255" required value="{{ old('email', $user->email) }}">
                                        @error('email') <p class="eu-error">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="ll-card eu-section">
                                <h3>Reset password</h3>
                                <p class="ll-muted">Optional. Leave both boxes empty to keep the current password.</p>
                                <div class="eu-grid">
                                    <div class="settings-field">
                                        <label for="password">New password</label>
                                        <div class="eu-pw">
                                            <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" placeholder="At least 8 characters, letters and numbers">
                                            <button type="button" data-toggle-pw>Show</button>
                                        </div>
                                        @error('password') <p class="eu-error">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="settings-field">
                                        <label for="password_confirmation">Confirm new password</label>
                                        <div class="eu-pw">
                                            <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" placeholder="Type it again">
                                        </div>
                                        <p class="eu-hint" id="pw-match" hidden></p>
                                    </div>
                                </div>
                                <div class="eu-notice">Changing the password signs this user out of every device they are logged in on.</div>
                            </div>

                            <div class="settings-actions">
                                <a href="{{ route('admin.users.show', $user->id) }}" class="table-btn">Cancel</a>
                                <button type="submit" class="settings-save-btn">Save changes</button>
                            </div>
                        </form>

                        <aside class="eu-side">
                            <div class="ll-card">
                                <div class="eu-person">
                                    <div class="eu-avatar">
                                        <span>{{ mb_strtoupper(mb_substr(trim($user->name), 0, 1)) }}</span>
                                        @if($user->avatar_url)<img src="{{ $user->avatar_url }}" alt="" onerror="this.remove()">@endif
                                    </div>
                                    <div>
                                        <strong>{{ $user->name }}</strong>
                                        <span class="ll-muted">{{ $user->email }}</span>
                                    </div>
                                </div>
                                <dl class="eu-facts">
                                    <div><dt>Role</dt><dd>{{ ucfirst($user->role) }}</dd></div>
                                    <div><dt>Status</dt><dd><span class="ll-badge {{ ['active' => 'active', 'offline' => 'inactive', 'suspended' => 'rejected'][$user->presence] }}">{{ ucfirst($user->presence) }}</span></dd></div>
                                    <div><dt>Member since</dt><dd>{{ $user->created_at->format('M j, Y') }}</dd></div>
                                    <div><dt>Last seen</dt><dd>{{ $user->presence === 'active' ? 'Online now' : ($user->last_seen_at ? $user->last_seen_at->diffForHumans() : 'Never') }}</dd></div>
                                </dl>
                                <p class="eu-hint" style="margin-top:14px;">Role is shown for reference only and cannot be changed from this page.</p>
                                <p style="margin:12px 0 0;"><a class="cell-link" href="{{ route('admin.users.show', $user->id) }}">View full details &rarr;</a></p>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

<script>
(function () {
    var pw = document.getElementById('password'), confirm = document.getElementById('password_confirmation'), msg = document.getElementById('pw-match');
    document.querySelector('[data-toggle-pw]').addEventListener('click', function () {
        var show = pw.type === 'password';
        pw.type = confirm.type = show ? 'text' : 'password';
        this.textContent = show ? 'Hide' : 'Show';
    });
    function check() {
        if (!pw.value && !confirm.value) { msg.hidden = true; return; }
        msg.hidden = false;
        var ok = pw.value === confirm.value;
        msg.textContent = ok ? 'Passwords match.' : 'Passwords do not match yet.';
        msg.style.color = ok ? '#47713E' : '#B14D48';
    }
    pw.addEventListener('input', check); confirm.addEventListener('input', check);
    document.getElementById('edit-user-form').addEventListener('submit', function (e) {
        if (pw.value !== confirm.value) { e.preventDefault(); check(); confirm.focus(); }
    });
})();
</script>

</body>
</html>
