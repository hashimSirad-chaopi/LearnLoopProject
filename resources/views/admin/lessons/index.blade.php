<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - Lesson Review</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .lr-avatar { border-radius: 11px; }
        .lr-avatar-lg { border-radius: 14px; }
    </style>
    @include('partials.admin-filters')
</head>
<body>

<div class="admin-container">

    @include('partials.admin-sidebar')

    <main class="main-content" data-live>
        <header class="top-header">
            <div class="header-left">
                <span class="header-label">ADMIN PANEL</span>
                <h1>Lesson Review</h1>
                <p>Lessons users wrote in the app (Profile → My Lessons). Nothing goes live until you approve it, unless the author is trusted in that skill.</p>
            </div>
        </header>

        <section class="users-page">
            <div class="ll-scroll">
                <div class="ll-page">
                    @include('partials.flash')
                    @include('partials.catalog-tabs')

                    <div class="ll-tabs">
                        <a class="ll-tab {{ $status === 'users' ? 'active' : '' }}" href="{{ route('admin.lessons') }}">Users</a>
                        <a class="ll-tab {{ $status === 'pending' ? 'active' : '' }}" href="{{ route('admin.lessons', ['status' => 'pending']) }}">Pending ({{ $counts['pending'] }})</a>
                        <a class="ll-tab {{ $status === 'published' ? 'active' : '' }}" href="{{ route('admin.lessons', ['status' => 'published']) }}">Published ({{ $counts['published'] }})</a>
                        <a class="ll-tab {{ $status === 'rejected' ? 'active' : '' }}" href="{{ route('admin.lessons', ['status' => 'rejected']) }}">Need change ({{ $counts['rejected'] }})</a>
                    </div>

                    @if($status === 'users')

                        {{-- ===== Users: pick a member, then "View lessons" ===== --}}
                        <div class="ll-toolbar">
                            <form method="GET" action="{{ route('admin.lessons') }}" class="ll-search">
                                <input type="text" inputmode="search" name="search" value="{{ $search }}" placeholder="Search users by name or email" aria-label="Search users">
                                <button type="submit" class="table-btn">Search</button>
                                @if($search !== '')
                                    <a href="{{ route('admin.lessons') }}" class="table-btn ll-link">Clear</a>
                                @endif
                            </form>
                        </div>

                        <span class="ll-muted">
                            {{ $members->count() }} {{ \Illuminate\Support\Str::plural('user', $members->count()) }} with lessons
                            @if($search !== '') &middot; matching &ldquo;{{ $search }}&rdquo; @endif
                            &middot; users with lessons waiting for review are listed first
                        </span>

                        <div class="ll-card">
                            <table class="ll-table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Lessons</th>
                                        <th>Pending</th>
                                        <th>Published</th>
                                        <th>Need change</th>
                                        <th>Last submitted</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse($members as $member)
                                    @php($row = $stats[$member->id])
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.lessons.member', $member) }}" class="lr-member">
                                                @include('partials.avatar', ['user' => $member, 'class' => 'lr-avatar'])
                                                <span class="lr-member-text">
                                                    <strong>{{ $member->name }}</strong>
                                                    <small>{{ $member->email }}</small>
                                                </span>
                                            </a>
                                        </td>
                                        <td>{{ (int) $row->total }}</td>
                                        <td>@if((int) $row->pending > 0)<span class="ll-badge pending">{{ (int) $row->pending }}</span>@else<span class="ll-muted">0</span>@endif</td>
                                        <td>@if((int) $row->published > 0)<span class="ll-badge published">{{ (int) $row->published }}</span>@else<span class="ll-muted">0</span>@endif</td>
                                        <td>@if((int) $row->rejected > 0)<span class="ll-badge rejected">{{ (int) $row->rejected }}</span>@else<span class="ll-muted">0</span>@endif</td>
                                        <td class="ll-muted" style="white-space:nowrap;">{{ $row->last_at ? \Illuminate\Support\Carbon::parse($row->last_at)->diffForHumans() : '—' }}</td>
                                        <td class="lr-right"><a href="{{ route('admin.lessons.member', $member) }}" class="table-btn ll-link">View lessons &rsaquo;</a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7">{{ $search !== '' ? 'No users match your search.' : 'No user has written a lesson yet.' }}</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                    @else

                        {{-- ===== Flat list for Pending / Published / Need change ===== --}}
                        <div class="ll-toolbar">
                            <form method="GET" action="{{ route('admin.lessons') }}" class="ll-search">
                                <input type="hidden" name="status" value="{{ $status }}">
                                <input type="text" inputmode="search" name="search" value="{{ $search }}" placeholder="Search lesson, skill or author" aria-label="Search lessons">
                                <button type="submit" class="table-btn">Search</button>
                                @if($search !== '')
                                    <a href="{{ route('admin.lessons', ['status' => $status]) }}" class="table-btn ll-link">Clear</a>
                                @endif
                            </form>
                        </div>
                        <span class="ll-muted">
                            {{ $lessons->count() }} {{ \Illuminate\Support\Str::plural('lesson', $lessons->count()) }}
                            @if($search !== '') &middot; matching &ldquo;{{ $search }}&rdquo; @endif
                        </span>

                        <div class="ll-card">
                            <table class="ll-table">
                                <thead>
                                    <tr>
                                        <th>Lesson</th>
                                        <th>Skill · level</th>
                                        <th>Author</th>
                                        <th>Status</th>
                                        <th>Submitted</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse($lessons as $lesson)
                                    <tr>
                                        <td><strong>{{ $lesson->title }}</strong></td>
                                        <td>{{ $lesson->skillLevel?->skill?->title ?? 'Unknown skill' }} · {{ ucfirst($lesson->skillLevel?->name ?? '') }}</td>
                                        <td>
                                            @if($lesson->author)
                                                <a href="{{ route('admin.lessons.member', $lesson->author) }}" class="lr-member">
                                                    @include('partials.avatar', ['user' => $lesson->author, 'class' => 'lr-avatar'])
                                                    <span class="lr-member-text"><strong>{{ $lesson->author->name }}</strong></span>
                                                </a>
                                            @else
                                                Deleted user
                                            @endif
                                        </td>
                                        <td>
                                            <span class="ll-badge {{ $lesson->status }}">{{ $lesson->status === 'rejected' ? 'Needs changes' : ucfirst($lesson->status) }}</span>
                                            @if($lesson->status === 'published' && ! $lesson->approved_by)
                                                <span class="ll-badge trusted" title="Published automatically because the author is trusted in this skill. Does not count toward trust.">Auto</span>
                                            @endif
                                        </td>
                                        <td>{{ $lesson->created_at->diffForHumans() }}</td>
                                        <td><a href="{{ route('admin.lessons.show', $lesson) }}" class="table-btn ll-link">{{ $lesson->status === 'pending' ? 'Review' : 'View' }}</a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6">{{ $search !== '' ? 'No lessons match your search.' : 'Nothing here. '.($status === 'pending' ? 'No lessons are waiting for review.' : '') }}</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                    @endif
                </div>
            </div>
        </section>
    </main>
</div>

</body>
</html>
