<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - {{ $user->name }}'s lessons</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .lr-avatar { border-radius: 11px; }
        .lr-avatar-lg { border-radius: 14px; }
    </style>
    @include('partials.admin-filters')
    <style>
        .lr-profile { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
        .lr-avatar-lg { width: 56px; height: 56px; font-size: 22px; }
    </style>
</head>
<body>

<div class="admin-container">

    @include('partials.admin-sidebar')

    <main class="main-content" data-live>
        <header class="top-header">
            <div class="header-left">
                <span class="header-label">ADMIN PANEL</span>
                <h1>{{ $user->name }}</h1>
                <p>{{ $user->email }} &middot; {{ $totals['all'] }} {{ \Illuminate\Support\Str::plural('lesson', $totals['all']) }} written</p>
            </div>
        </header>

        <section class="users-page">
            <div class="ll-scroll">
                <div class="ll-page">
                    @include('partials.flash')

                    <div class="ll-card lr-profile">
                        <div class="lr-member" style="gap:14px;">
                            @include('partials.avatar', ['user' => $user, 'class' => 'lr-avatar lr-avatar-lg'])
                            <span class="lr-member-text">
                                <strong style="font-size:17px;">{{ $user->name }}</strong>
                                <small style="max-width:none;">{{ $user->email }}@if($totals['last']) &middot; last lesson {{ \Illuminate\Support\Carbon::parse($totals['last'])->diffForHumans() }}@endif</small>
                            </span>
                        </div>
                        <div class="ll-row">
                            @if($user->status === 'suspended')<span class="ll-badge rejected">Suspended</span>@endif
                            <a href="{{ route('admin.users.show', $user) }}" class="table-btn ll-link">View profile &rsaquo;</a>
                        </div>
                    </div>

                    <div class="ll-row between">
                        <a href="{{ route('admin.lessons') }}" class="table-btn ll-link">&larr; Users</a>
                        <div class="ll-row">
                            <span class="ll-badge pending">Pending {{ $totals['pending'] }}</span>
                            <span class="ll-badge published">Published {{ $totals['published'] }}</span>
                            <span class="ll-badge rejected">Need change {{ $totals['rejected'] }}</span>
                        </div>
                    </div>

                    {{-- ===== Search: category, skill or lesson title ===== --}}
                    <div class="ll-toolbar">
                        <form method="GET" action="{{ route('admin.lessons.member', $user) }}" class="ll-search">
                            <input type="text" inputmode="search" name="search" value="{{ $search }}" placeholder="Search category, skill or lesson" aria-label="Search this user's lessons">
                            <button type="submit" class="table-btn">Search</button>
                            @if($search !== '')
                                <a href="{{ route('admin.lessons.member', $user) }}" class="table-btn ll-link">Clear</a>
                            @endif
                        </form>
                    </div>

                    @if($search !== '')
                        <span class="ll-muted">Showing results for &ldquo;{{ $search }}&rdquo;</span>
                    @endif

                    {{-- ===== Category > skills ===== --}}
                    @forelse($categories as $categoryName => $skills)
                        <div class="ll-card">
                            <h3 class="lr-category">{{ $categoryName }}</h3>
                            <div class="lr-list">
                                @foreach($skills as $row)
                                    <a href="{{ route('admin.lessons.member.skill', [$user, $row['skill']]) }}" class="lr-skill-row">
                                        <span class="lr-skill-icon">{{ $row['skill']->icon }}</span>
                                        <span class="lr-grow">
                                            <strong>{{ $row['skill']->title }}</strong>
                                            <small>
                                                {{ $row['total'] }} {{ \Illuminate\Support\Str::plural('lesson', $row['total']) }}
                                                @if($row['matches']->isNotEmpty())
                                                    &middot; matches: {{ $row['matches']->implode(', ') }}
                                                @endif
                                            </small>
                                        </span>
                                        @if($row['pending'] > 0)<span class="ll-badge pending">{{ $row['pending'] }} pending</span>@endif
                                        @if($row['published'] > 0)<span class="ll-badge published">{{ $row['published'] }} published</span>@endif
                                        @if($row['rejected'] > 0)<span class="ll-badge rejected">{{ $row['rejected'] }} need change</span>@endif
                                        <span class="lr-chevron" aria-hidden="true">&rsaquo;</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="ll-card"><p class="ll-muted">No category, skill or lesson matches &ldquo;{{ $search }}&rdquo;.</p></div>
                    @endforelse
                </div>
            </div>
        </section>
    </main>
</div>

</body>
</html>
