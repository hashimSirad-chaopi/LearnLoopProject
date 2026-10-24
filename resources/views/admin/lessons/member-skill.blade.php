<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - {{ $user->name }} &middot; {{ $skill->title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div class="admin-container">

    @include('partials.admin-sidebar')

    <main class="main-content" data-live>
        <header class="top-header">
            <div class="header-left">
                <span class="header-label">ADMIN PANEL</span>
                <h1>{{ $skill->icon }} {{ $skill->title }}</h1>
                <p>{{ $skill->category?->name ?? 'Uncategorized' }} &middot; lessons written by {{ $user->name }}</p>
            </div>
        </header>

        <section class="users-page">
            <div class="ll-scroll">
                <div class="ll-page">
                    @include('partials.flash')

                    <div class="ll-row">
                        <a href="{{ route('admin.lessons') }}" class="table-btn ll-link">Users</a>
                        <span class="ll-muted">&rsaquo;</span>
                        <a href="{{ route('admin.lessons.member', $user) }}" class="table-btn ll-link">{{ $user->name }}</a>
                        <span class="ll-muted">&rsaquo;</span>
                        <strong class="ll-muted">{{ $skill->category?->name ?? 'Uncategorized' }} / {{ $skill->title }}</strong>
                    </div>

                    @foreach($levels as $levelName => $lessons)
                        <div class="ll-card">
                            <h3 class="lr-category">{{ ucfirst($levelName) }}</h3>
                            <p class="ll-muted">
                                {{ $lessons->count() }} {{ \Illuminate\Support\Str::plural('lesson', $lessons->count()) }}
                                &middot; numbered in the order learners get them
                                @if($lessons->count() > 1)
                                    &middot; use &#9650; &#9660; to rearrange (the change also shows in {{ $user->name }}'s app)
                                @endif
                            </p>

                            <div class="lr-list">
                                @foreach($lessons as $lesson)
                                    <div class="lr-lesson-row" id="lesson-{{ $lesson->id }}">
                                        <span class="lr-num">{{ $loop->iteration }}</span>
                                        <span class="lr-grow">
                                            <strong>{{ $lesson->title }}</strong>
                                            <small>
                                                Submitted {{ $lesson->created_at->diffForHumans() }}
                                                @if($lesson->ratings_count > 0)
                                                    &middot; {{ number_format($lesson->ratings_avg_stars, 1) }}&#9733; from {{ $lesson->ratings_count }}
                                                @endif
                                            </small>
                                        </span>
                                        <span class="ll-badge {{ $lesson->status }}">{{ $lesson->status === 'rejected' ? 'Needs changes' : ucfirst($lesson->status) }}</span>
                                        @if($lesson->status === 'published' && ! $lesson->approved_by)
                                            <span class="ll-badge trusted" title="Published automatically because the author is trusted in this skill. Does not count toward trust.">Auto</span>
                                        @endif
                                        @if($lessons->count() > 1)
                                            <form method="POST" action="{{ route('admin.lessons.member.skill.move', [$user, $skill]) }}" class="lr-move-form">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="lesson_id" value="{{ $lesson->id }}">
                                                <button type="submit" name="direction" value="up" class="lr-move" title="Move up" aria-label="Move up" @disabled($loop->first)>&#9650;</button>
                                                <button type="submit" name="direction" value="down" class="lr-move" title="Move down" aria-label="Move down" @disabled($loop->last)>&#9660;</button>
                                            </form>
                                        @endif
                                        <a href="{{ route('admin.lessons.show', $lesson) }}" class="table-btn ll-link">{{ $lesson->status === 'pending' ? 'Review' : 'View' }}</a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </main>
</div>

</body>
</html>
