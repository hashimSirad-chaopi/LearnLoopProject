<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - Reports</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.admin-filters')
</head>
<body>

<div class="admin-container">

    @include('partials.admin-sidebar')

    <main class="main-content" data-live>

        <header class="top-header">
            <div class="header-left">
                <span class="header-label">ADMIN PANEL</span>
                <h1>Reports</h1>
                <p>Complaints users filed about other users or their listings. Decide what happens: close it, warn the user, or suspend the account.</p>
            </div>
        </header>

        <section class="users-page">
            <div class="ll-scroll">
                <div class="ll-page">
                    @include('partials.flash')
@include('partials.user-filter', ['what' => 'reports'])

                    {{-- ===== Status cards (they filter the list) ===== --}}
                    <div class="um-stats rp-stats">
                        @foreach(['pending' => 'Pending', 'resolved' => 'Resolved', 'dismissed' => 'Dismissed', 'all' => 'All reports'] as $key => $label)
                            <a class="um-stat rp-stat-{{ $key }} {{ $statusFilter === $key ? 'active' : '' }}"
                               href="{{ route('admin.reports', array_filter(['user' => request('user'), 'search' => $search, 'status' => $key === 'all' ? null : $key])) }}">
                                <span class="um-stat-label">{{ $label }}</span>
                                <strong>{{ number_format($counts[$key]) }}</strong>
                                @if($key === 'pending' && $counts['pending'] > 0)<small class="rp-stat-hint">need your review</small>@endif
                            </a>
                        @endforeach
                    </div>

                    {{-- ===== Search ===== --}}
                    <div class="ll-toolbar">
                        <form method="GET" action="{{ route('admin.reports') }}" class="ll-search">
                            @if(request('user'))<input type="hidden" name="user" value="{{ request('user') }}">@endif
                            @if($statusFilter !== 'all')<input type="hidden" name="status" value="{{ $statusFilter }}">@endif
                            <input type="text" inputmode="search" name="search" value="{{ $search }}" placeholder="Search by title or reason" aria-label="Search reports">
                            <button type="submit" class="table-btn">Search</button>
                            @if($search !== '')
                                <a href="{{ route('admin.reports', array_filter(['status' => $statusFilter === 'all' ? null : $statusFilter])) }}" class="table-btn ll-link">Clear</a>
                            @endif
                        </form>
                        <span class="ll-muted">
                            {{ $reports->total() }} {{ \Illuminate\Support\Str::plural('report', $reports->total()) }}
                            @if($search !== '') matching &ldquo;{{ $search }}&rdquo; @endif
                        </span>
                    </div>

                    {{-- ===== Bulk bar (only useful when something is pending) ===== --}}
                    @if($reports->where('status', 'pending')->isNotEmpty())
                        <form id="bulk-reports" method="POST" action="{{ route('admin.reports.bulk') }}" class="bulk-bar">
                            @csrf
                            <label class="bulk-all"><input type="checkbox" data-select-all="bulk-reports"> Select all pending</label>
                            <span class="bulk-count"></span>
                            <span class="bulk-actions">
                                <button type="submit" name="bulk" value="resolve" class="table-btn" onclick="return confirm('Resolve the selected reports? (No warning or suspension is sent in bulk.)');">Resolve selected</button>
                                <button type="submit" name="bulk" value="dismiss" class="table-btn" onclick="return confirm('Dismiss the selected reports?');">Dismiss selected</button>
                            </span>
                        </form>
                    @endif

                    {{-- ===== Reports ===== --}}
                    @forelse($reports as $report)
                        @php
                            $reported = $report->reportedUser;
                            $parts = str_contains($report->reason, ' — ') ? explode(' — ', $report->reason, 2) : [null, $report->reason];
                            [$type, $details] = $parts;
                            $up = (int) ($upheld[$report->reported_user_id] ?? 0);
                            $warn = (int) ($warnings[$report->reported_user_id] ?? 0);
                        @endphp

                        <article class="rp-card {{ $report->status }}" id="report-{{ $report->id }}">
                            <header class="rp-head">
                                @if($report->status === 'pending')
                                    <input type="checkbox" name="ids[]" value="{{ $report->id }}" form="bulk-reports" class="bulk-check" aria-label="Select this report">
                                @endif
                                <div class="rp-title">
                                    <h3>{{ $report->title }}</h3>
                                    <span class="ll-muted">Filed by {{ $report->reporter->name ?? 'Unknown' }} &middot; {{ $report->created_at->diffForHumans() }}</span>
                                </div>
                                <span class="rp-badge {{ $report->status }}">{{ ucfirst($report->status) }}</span>
                            </header>

                            <div class="rp-meta">
                                <span class="rp-chip against">
                                    Against
                                    @if($reported)
                                        <a href="{{ route('admin.users.show', $reported) }}"><strong>{{ $reported->name }}</strong></a>
                                    @else
                                        <strong>a deleted user</strong>
                                    @endif
                                </span>
                                @if($report->listing)
                                    <span class="rp-chip">Listing: <strong>{{ \Illuminate\Support\Str::limit($report->listing->title, 40) }}</strong></span>
                                @endif
                                @if($type)
                                    <span class="rp-chip type">{{ $type }}</span>
                                @endif
                                @if($reported && $reported->status === 'suspended')
                                    <span class="status-badge suspended-status">Suspended</span>
                                @endif
                            </div>

                            <blockquote class="lr-quote">{{ $details }}</blockquote>

                            @if($report->attachments->isNotEmpty())
                                <div class="rp-proof" style="display:flex;flex-wrap:wrap;gap:8px;margin:8px 0;">
                                    @foreach($report->attachments as $proof)
                                        @if($proof->isVideo())
                                            <video src="{{ $proof->url }}" controls preload="metadata" style="max-width:220px;max-height:160px;border-radius:8px;"></video>
                                        @else
                                            <a href="{{ $proof->url }}" target="_blank" rel="noopener"><img src="{{ $proof->url }}" alt="Report proof" loading="lazy" style="max-width:160px;max-height:120px;border-radius:8px;object-fit:cover;"></a>
                                        @endif
                                    @endforeach
                                </div>
                            @endif

                            @if($reported)
                                <div class="rp-history" title="Reports an admin upheld against this user. At {{ $autoSuspendAfter }} the account is suspended automatically.">
                                    <span class="rp-history-label">User history</span>
                                    @if($autoSuspendAfter > 0)
                                        <span class="rp-pips" aria-hidden="true">
                                            @for($i = 1; $i <= $autoSuspendAfter; $i++)<i class="rp-pip {{ $i <= $up ? 'on' : '' }}"></i>@endfor
                                        </span>
                                    @endif
                                    <span>{{ $up }} upheld {{ \Illuminate\Support\Str::plural('report', $up) }} &middot; {{ $warn }} {{ \Illuminate\Support\Str::plural('warning', $warn) }}</span>
                                    @if($autoSuspendAfter > 0)<span class="ll-muted">(auto-suspend at {{ $autoSuspendAfter }})</span>@endif
                                </div>
                            @endif

                            @if($report->status === 'resolved')
                                <p class="rp-outcome">
                                    <strong>{{ ['none' => 'Closed, no action on the user', 'warn' => 'User was warned', 'suspend' => 'User was suspended'][$report->action_taken] ?? 'Resolved' }}</strong>
                                    @if($report->admin_note) &middot; &ldquo;{{ $report->admin_note }}&rdquo; @endif
                                    @if($report->resolved_at)<span class="ll-muted"> &middot; {{ $report->resolved_at->diffForHumans() }}</span>@endif
                                </p>
                            @elseif($report->status === 'dismissed')
                                <p class="rp-outcome muted"><strong>Dismissed</strong> &middot; not a valid complaint</p>
                            @endif

                            <footer class="rp-foot">
                                <div class="rp-actions">
                                    @if($report->status === 'pending')
                                        <button type="button" class="rp-btn primary" data-rp-toggle="resolve-{{ $report->id }}" aria-expanded="false" aria-controls="resolve-{{ $report->id }}">Resolve&hellip;</button>
                                        <form method="POST" action="{{ route('admin.reports.dismiss', $report->id) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="rp-btn">Dismiss</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.reports.delete', $report->id) }}" class="rp-delete" onsubmit="return confirm('Delete this report for good?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="rp-btn danger">Delete</button>
                                    </form>
                                </div>

                                @if($report->status === 'pending')
                                    <div class="rp-resolve-panel" id="resolve-{{ $report->id }}" hidden>
                                        <form method="POST" action="{{ route('admin.reports.resolve', $report->id) }}" class="rp-resolve-form">
                                            @csrf @method('PATCH')

                                            <fieldset class="rp-choices">
                                                <legend>What should happen to {{ $reported->name ?? 'the user' }}?</legend>
                                                <label class="rp-choice"><input type="radio" name="action" value="none" checked>
                                                    <span><strong>No action</strong><small>The complaint is valid, but nothing happens to the user.</small></span></label>
                                                <label class="rp-choice"><input type="radio" name="action" value="warn">
                                                    <span><strong>Send a warning</strong><small>The user is warned and told why.</small></span></label>
                                                <label class="rp-choice"><input type="radio" name="action" value="suspend">
                                                    <span><strong>Suspend the account</strong><small>Choose a reason and how long below.</small></span></label>
                                            </fieldset>

                                            <div class="rp-suspend-fields" hidden>
                                                <label>Reason
                                                    <select name="reason">
                                                        @foreach(\App\Support\Suspension::REASONS as $key => $label)
                                                            <option value="{{ $key }}" {{ $key === 'rules' ? 'selected' : '' }}>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </label>
                                                <label>For how long
                                                    <select name="duration">
                                                        @foreach(\App\Support\Suspension::OPTIONS as $key => $label)
                                                            <option value="{{ $key }}" {{ $key === '1w' ? 'selected' : '' }}>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </label>
                                            </div>

                                            <label class="rp-note"><span>Note <span class="ll-muted" data-note-hint>(kept on the report, optional)</span></span>
                                                <textarea name="note" rows="2" maxlength="500" placeholder="Optional"></textarea>
                                            </label>

                                            <button type="submit" class="rp-btn primary">Confirm</button>
                                        </form>
                                    </div>
                                @endif
                            </footer>
                        </article>
                    @empty
                        <div class="ll-card rp-empty">
                            @if($search !== '')
                                <strong>No reports match &ldquo;{{ $search }}&rdquo;.</strong>
                            @elseif($statusFilter === 'pending')
                                <strong>You're all caught up.</strong>
                                <span class="ll-muted">No reports are waiting for review.</span>
                            @else
                                <strong>No reports here yet.</strong>
                                <span class="ll-muted">When a user reports someone in the app, it shows up here.</span>
                            @endif
                        </div>
                    @endforelse

                    {{ $reports->links('vendor.pagination.admin') }}
                </div>
            </div>
        </section>
    </main>
</div>

{{-- Delegated, so it keeps working after a live update replaces the page content. --}}
<script>
    // Open / close a report's Resolve panel.
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-rp-toggle]');
        if (!button) return;

        var panel = document.getElementById(button.getAttribute('data-rp-toggle'));
        var opening = panel.hidden;
        panel.hidden = !opening;
        button.setAttribute('aria-expanded', opening ? 'true' : 'false');
        button.textContent = opening ? 'Close' : 'Resolve\u2026';
        button.classList.toggle('open', opening);
    });

    document.addEventListener('change', function (event) {
        var form = event.target.closest('.rp-resolve-form');
        if (!form) return;

        var action = form.querySelector('input[name=action]:checked').value;
        var other = action === 'suspend' && form.querySelector('select[name=reason]').value === 'other';
        var note = form.querySelector('textarea[name=note]');

        form.querySelector('.rp-suspend-fields').hidden = action !== 'suspend';
        note.required = other;
        form.querySelector('[data-note-hint]').textContent = other
            ? '(required: explain the reason)'
            : (action === 'none' ? '(kept on the report, optional)' : '(shown to the user, optional)');
    });
</script>

</body>
</html>
