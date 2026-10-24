<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - Recovery Requests</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .lr-avatar { border-radius: 11px; }
        .lr-avatar-lg { border-radius: 14px; }
    </style>
</head>
<body>

<div class="admin-container">

    @include('partials.admin-sidebar')

    <main class="main-content" data-live>
        <header class="top-header">
            <div class="header-left">
                <span class="header-label">ADMIN PANEL</span>
                <h1>Recovery Requests</h1>
                <p>Suspended members cannot log in, so they ask you from the app's login screen. Reactivate the account, or decline and tell them why.</p>
            </div>
        </header>

        <section class="users-page">
            <div class="ll-scroll">
                <div class="ll-page">
                    @include('partials.flash')

                    <div class="ll-tabs">
                        <a class="ll-tab {{ $status === 'pending' ? 'active' : '' }}" href="{{ route('admin.appeals') }}">Pending ({{ $counts['pending'] }})</a>
                        <a class="ll-tab {{ $status === 'approved' ? 'active' : '' }}" href="{{ route('admin.appeals', ['status' => 'approved']) }}">Reactivated ({{ $counts['approved'] }})</a>
                        <a class="ll-tab {{ $status === 'declined' ? 'active' : '' }}" href="{{ route('admin.appeals', ['status' => 'declined']) }}">Declined ({{ $counts['declined'] }})</a>
                    </div>

                    @forelse($appeals as $appeal)
                        @php($member = $appeal->user)
                        <div class="ll-card">
                            <div class="lr-appeal-head">
                                <a href="{{ route('admin.users.show', $member) }}" class="lr-member">
                                    @include('partials.avatar', ['user' => $member, 'class' => 'lr-avatar'])
                                    <span class="lr-member-text">
                                        <strong>{{ $member->name }}</strong>
                                        <small>{{ $member->email }}</small>
                                    </span>
                                </a>
                                <div class="ll-row">
                                    <span class="ll-badge {{ $member->status === 'active' ? 'published' : 'rejected' }}">Account {{ $member->status }}</span>
                                    @if(($upheld[$member->id] ?? 0) > 0)
                                        <span class="ll-badge pending">{{ $upheld[$member->id] }} upheld {{ \Illuminate\Support\Str::plural('report', $upheld[$member->id]) }}</span>
                                    @endif
                                </div>
                            </div>

                            @if($why = \App\Support\Suspension::describe($member->suspension_reason, $member->suspension_note))
                                <p class="ll-muted"><strong>Suspended for:</strong> {{ $why }}</p>
                            @endif
                            <p class="ll-muted">Sent {{ $appeal->created_at->diffForHumans() }} &middot; {{ $appeal->created_at->format('M d, Y g:i A') }}</p>
                            <blockquote class="lr-quote">{{ $appeal->message }}</blockquote>

                            @if($appeal->status === 'pending')
                                <form method="POST" class="lr-decision">
                                    @csrf @method('PATCH')
                                    <label for="note-{{ $appeal->id }}" class="ll-muted">Note to the member (required to decline, optional to reactivate)</label>
                                    <textarea id="note-{{ $appeal->id }}" name="note" rows="2" maxlength="500" class="lr-note" placeholder="For example: Thanks for explaining, please follow the community rules."></textarea>
                                    <div class="ll-row">
                                        <button type="submit" formaction="{{ route('admin.appeals.approve', $appeal) }}" class="table-btn"
                                                onclick="return confirm('Reactivate {{ addslashes($member->name) }}\'s account?');">Reactivate account</button>
                                        <button type="submit" formaction="{{ route('admin.appeals.decline', $appeal) }}" class="table-btn ll-link"
                                                onclick="var n = this.form.note; if (!n.value.trim()) { n.focus(); alert('Write a short reason so the member knows why.'); return false; } return confirm('Decline this request?');">Decline</button>
                                    </div>
                                </form>
                            @else
                                <p class="ll-muted">
                                    <strong>{{ $appeal->status === 'approved' ? 'Reactivated' : 'Declined' }}</strong>
                                    by {{ $appeal->reviewer->name ?? 'an administrator' }}
                                    {{ $appeal->reviewed_at ? $appeal->reviewed_at->diffForHumans() : '' }}
                                    @if($appeal->admin_note) &middot; &ldquo;{{ $appeal->admin_note }}&rdquo; @endif
                                </p>
                            @endif
                        </div>
                    @empty
                        <div class="ll-card"><p class="ll-muted">
                            {{ $status === 'pending' ? 'No recovery requests are waiting. 🎉' : 'Nothing here yet.' }}
                        </p></div>
                    @endforelse
                </div>
            </div>
        </section>
    </main>
</div>

</body>
</html>
