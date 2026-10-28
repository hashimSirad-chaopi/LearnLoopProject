<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - Comments</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.admin-filters')
    <style>
        .cmt-text, .cmt-short, .cmt-full { white-space: pre-line; overflow-wrap: anywhere; }
        .cmt .cmt-full { display: none; }
        .cmt.open .cmt-full { display: block; }
        .cmt.open .cmt-short { display: none; }
        .cmt-toggle { background: none; border: 0; padding: 4px 0 0; cursor: pointer; color: #1f6f4a; font-weight: 700; font-size: 12.5px; }
        .cmt-toggle:hover { text-decoration: underline; }
        .cmt-toggle:focus:not(:focus-visible) { outline: none; box-shadow: none; }
        .cmt-len { color: #6b7780; font-size: 11.5px; margin-left: 6px; }
        .cmt-tools { display: flex; gap: 12px; align-items: center; }
        .cmt-tools button { background: none; border: 0; padding: 0; cursor: pointer; color: #1f6f4a; font-weight: 700; font-size: 12.5px; }
        .cmt-tools button:hover { text-decoration: underline; }
        tr:has(.bulk-check:checked) { background: #f1f8f4; }
        .bulk-bar { position: sticky; top: 0; z-index: 5; flex-wrap: wrap; row-gap: 8px; background: #fff; box-shadow: 0 2px 8px rgba(0, 0, 0, .06); }
        .bulk-bar .bulk-actions { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }

        /* Comments table: the Comment column gets the free space (the shared user-table rules give it to column 2) */
        .users-table.um-table.cm-table { min-width: 900px; }
        .users-table.um-table.cm-table th:nth-child(2),
        .users-table.um-table.cm-table td:nth-child(2) { width: 160px; min-width: 140px; white-space: normal; }
        .users-table.um-table.cm-table th:nth-child(3),
        .users-table.um-table.cm-table td:nth-child(3) { width: 100%; min-width: 320px; white-space: normal; }
        .users-table.um-table.cm-table th { vertical-align: middle; }
        .users-table.um-table.cm-table td.um-hide-sm.cm-listing { white-space: normal; min-width: 150px; max-width: 220px; }
        .users-table.um-table.cm-table td { vertical-align: top; }
        .users-table.um-table.cm-table td.um-check { padding-top: 16px; }
        .users-table.um-table.cm-table td.um-actions-col .um-actions { align-items: center; gap: 6px; }
    </style>
</head>
<body>

<div class="admin-container">

    @include('partials.admin-sidebar')

    <main class="main-content" data-live>
        <header class="top-header">
            <div class="header-left">
                <span class="header-label">ADMIN PANEL</span>
                <h1>Comments</h1>
                <p>What users write under listings in the app. Remove anything that breaks the rules; the user is told why.</p>
            </div>
        </header>

        <section class="users-page">
            <div class="ll-scroll">
                <div class="ll-page">
                    @include('partials.flash')
@include('partials.user-filter', ['what' => 'comments'])

                    {{-- ===== Filter tabs ===== --}}
                    <div class="um-stats">
                        <a class="um-stat {{ $type === 'all' ? 'active' : '' }}" href="{{ route('admin.comments', array_filter(['user' => request('user'), 'search' => $search])) }}">
                            <span class="um-stat-label">All</span>
                            <strong>{{ number_format($counts['all']) }}</strong>
                        </a>
                        <a class="um-stat {{ $type === 'comments' ? 'active' : '' }}" href="{{ route('admin.comments', array_filter(['user' => request('user'), 'search' => $search, 'type' => 'comments'])) }}">
                            <span class="um-stat-label">Comments</span>
                            <strong>{{ number_format($counts['comments']) }}</strong>
                        </a>
                        <a class="um-stat {{ $type === 'replies' ? 'active' : '' }}" href="{{ route('admin.comments', array_filter(['user' => request('user'), 'search' => $search, 'type' => 'replies'])) }}">
                            <span class="um-stat-label">Replies</span>
                            <strong>{{ number_format($counts['replies']) }}</strong>
                        </a>
                        <div class="um-stat plain">
                            <span class="um-stat-label">Written today</span>
                            <strong>{{ number_format($counts['today']) }}</strong>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('admin.comments') }}" class="ll-search grow">
                        @if(request('user'))<input type="hidden" name="user" value="{{ request('user') }}">@endif
                        @if($type !== 'all')<input type="hidden" name="type" value="{{ $type }}">@endif
                        <input type="search" name="search" value="{{ $search }}" placeholder="Search comment text, user or listing" aria-label="Search comments">
                        <button type="submit" class="table-btn">Search</button>
                        @if($search !== '')
                            <a href="{{ route('admin.comments', array_filter(['type' => $type !== 'all' ? $type : null])) }}" class="table-btn ll-link">Clear</a>
                        @endif
                    </form>

                    <span class="ll-row between">
                    <span class="ll-muted">
                        {{ number_format($comments->total()) }} {{ \Illuminate\Support\Str::plural('comment', $comments->total()) }}
                        @if($type !== 'all') &middot; {{ $type }} only @endif
                        @if($search !== '') &middot; matching &ldquo;{{ $search }}&rdquo; @endif
                    </span>
                    <span class="cmt-tools">
                        <button type="button" data-cmt-all="open">Expand all</button>
                        <button type="button" data-cmt-all="close">Collapse all</button>
                    </span>
                    </span>

                    {{-- ===== Act on several at once ===== --}}
                    @if($comments->isNotEmpty())
                        <form id="bulk-comments" method="POST" action="{{ route('admin.comments.bulk') }}" class="bulk-bar">
                            @csrf
                            <label class="bulk-all"><input type="checkbox" data-select-all="bulk-comments"> Select all on this page ({{ $comments->count() }})</label>
                            <span class="bulk-count"></span>

                            <span class="bulk-actions">
                                <strong>Remove</strong>
                                <select name="reason" aria-label="Reason for removing the selected comments">
                                    @foreach($reasons as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" name="action" value="remove" class="table-btn" onclick="return confirm('Remove the selected comments, and any replies under them? Each user is told why.');">Remove selected</button>
                            </span>

                            <span class="bulk-actions">
                                <strong>Suspend the authors</strong>
                                <select name="duration" aria-label="Suspend the authors for">
                                    @foreach(\App\Support\Suspension::OPTIONS as $key => $label)
                                        <option value="{{ $key }}" {{ $key === '1w' ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <select name="suspend_reason" aria-label="Reason for suspending">
                                    @foreach(\App\Support\Suspension::REASONS as $key => $label)
                                        <option value="{{ $key }}" {{ $key === 'rules' ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="note" maxlength="500" placeholder='Details (required for "Other")' aria-label="Details">
                                <button type="submit" name="action" value="suspend" class="table-btn" onclick="return confirm('Suspend the authors of the selected comments? Their comments stay. Admins, yourself and users already suspended are skipped.');">Suspend authors</button>
                                <button type="submit" name="action" value="both" class="table-btn" onclick="return confirm('Suspend the authors AND remove the selected comments (and replies under them)? Each user is told why.');">Remove &amp; suspend</button>
                            </span>
                        </form>
                    @endif

                    {{-- ===== List ===== --}}
                    <div class="users-table-container um-table-wrap">
                        <table class="users-table um-table cm-table">
                            <thead>
                                <tr>
                                    <th class="um-check"></th>
                                    <th>User</th>
                                    <th>Comment</th>
                                    <th class="um-hide-sm">On listing</th>
                                    <th class="um-hide-sm">When</th>
                                    <th class="um-actions-col">Remove</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($comments as $comment)
                                    <tr id="comment-{{ $comment->id }}">
                                        <td class="um-check">
                                            <input type="checkbox" name="ids[]" value="{{ $comment->id }}" form="bulk-comments" class="bulk-check" aria-label="Select comment by {{ $comment->user?->name ?? 'a deleted user' }}">
                                        </td>

                                        <td>
                                            @if($comment->user)
                                                <a href="{{ route('admin.users.show', $comment->user) }}" class="cell-link"><strong>{{ $comment->user->name }}</strong></a>
                                                @if($comment->user->status === 'suspended')<br><span class="status-badge suspended-status">Suspended</span>@endif
                                            @else
                                                <strong>Deleted user</strong>
                                            @endif
                                        </td>

                                        <td class="cm-comment">
                                            @if($comment->parent_id)<span class="ll-badge">Reply</span>@endif
                                            @if($comment->parent)
                                                <small class="ll-muted" style="display:block;border-left:3px solid #d8dee4;padding-left:8px;margin:4px 0;">
                                                    Replying to {{ $comment->parent->user?->name ?? 'a user' }}: &ldquo;{{ \Illuminate\Support\Str::limit($comment->parent->body, 70) }}&rdquo;
                                                </small>
                                            @endif
                                @if(mb_strlen($comment->body) > 300)
                                                <div class="cmt" data-cmt="{{ $comment->id }}">
                                                    <div class="cmt-short">{{ \Illuminate\Support\Str::limit($comment->body, 300) }}</div>
                                                    <div class="cmt-full">{{ $comment->body }}</div>
                                                    <button type="button" class="cmt-toggle" data-cmt-toggle>See more</button>
                                                    <span class="cmt-len">{{ number_format(mb_strlen($comment->body)) }} characters</span>
                                                </div>
                                            @else
                                                <div class="cmt-text">{{ $comment->body }}</div>
                                            @endif
                                            @if($comment->replies_count > 0)
                                                <small class="ll-muted">{{ $comment->replies_count }} {{ \Illuminate\Support\Str::plural('reply', $comment->replies_count) }}</small>
                                            @endif
                                        </td>

                                        <td class="um-hide-sm cm-listing">
                                            @if($comment->listing)
                                                <a href="{{ route('admin.listings.edit', $comment->listing) }}" class="cell-link">{{ \Illuminate\Support\Str::limit($comment->listing->title, 40) }}</a>
                                            @else
                                                <span class="ll-muted">Deleted listing</span>
                                            @endif
                                        </td>

                                        <td class="um-hide-sm" title="{{ $comment->created_at->format('M j, Y g:i A') }}">{{ $comment->created_at->diffForHumans() }}</td>

                                        <td class="um-actions-col">
                                            <form method="POST" action="{{ route('admin.comments.delete', $comment) }}" class="um-actions"
                                                  onsubmit="return confirm('{{ $comment->replies_count > 0 ? 'Remove this comment and its '.$comment->replies_count.' '.\Illuminate\Support\Str::plural('reply', $comment->replies_count).'?' : 'Remove this comment?' }}');">
                                                @csrf @method('DELETE')
                                                <select name="reason" class="lr-select" aria-label="Reason for removing this comment">
                                                    @foreach($reasons as $key => $label)
                                                        <option value="{{ $key }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="delete-btn">Remove</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="empty-users">
                                            {{ $search !== '' || $type !== 'all' ? 'Nothing matches these filters.' : 'Nobody has commented on a listing yet.' }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $comments->links('vendor.pagination.admin') }}
                </div>
            </div>
        </section>
    </main>
</div>

<script>
    // See more / See less on long comments. Opened comments stay open when the page refreshes itself.
    (function () {
        var opened = new Set();

        function apply() {
            document.querySelectorAll('.cmt[data-cmt]').forEach(function (el) {
                var isOpen = opened.has(el.dataset.cmt);
                el.classList.toggle('open', isOpen);
                var button = el.querySelector('[data-cmt-toggle]');
                if (button) button.textContent = isOpen ? 'See less' : 'See more';
            });
        }

        document.addEventListener('click', function (event) {
            var toggle = event.target.closest('[data-cmt-toggle]');
            if (toggle) {
                var id = toggle.closest('.cmt').dataset.cmt;
                if (opened.has(id)) opened.delete(id); else opened.add(id);
                apply();
                return;
            }
            var all = event.target.closest('[data-cmt-all]');
            if (all) {
                document.querySelectorAll('.cmt[data-cmt]').forEach(function (el) {
                    if (all.dataset.cmtAll === 'open') opened.add(el.dataset.cmt); else opened.delete(el.dataset.cmt);
                });
                apply();
            }
        });

        document.addEventListener('live:updated', apply);
    })();
</script>

</body>
</html>
