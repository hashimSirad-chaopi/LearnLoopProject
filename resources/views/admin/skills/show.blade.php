<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - {{ $skill->title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div class="admin-container">

    @include('partials.admin-sidebar')

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <span class="header-label">ADMIN PANEL</span>
                <h1>{{ $skill->icon }} {{ $skill->title }}</h1>
                <p>{{ $skill->category?->name ?? 'No category' }} &middot; {{ ucfirst($skill->status) }}</p>
            </div>
        </header>

        <section class="users-page">
            <div class="ll-scroll">
                <div class="ll-page">
                    @include('partials.flash')
                    @include('partials.catalog-tabs')

                    @include('partials.crumbs', ['items' => array_values(array_filter([
                        ['Skills & lessons', route('admin.skills')],
                        $skill->category ? [$skill->category->name, route('admin.skills', ['category' => $skill->category_id])] : null,
                        [$skill->title],
                    ]))])

                    @php($catalogLessonCount = $skill->levels->sum(fn ($l) => $l->lessons->whereNull('author_id')->count()))
                    @php($questionCount = $skill->levels->sum(fn ($l) => $l->lessons->whereNull('author_id')->sum(fn ($x) => $x->questions->count())))

                    @if($skill->experience_based)
                        <div class="ll-card ll-row between">
                            <p class="ll-muted" style="margin:0">
                                <strong>Experience skill.</strong> It has no lessons or levels. Users unlock Professional by sending a certification, which you approve under Professional requests.
                            </p>
                            <a href="{{ route('admin.professional') }}" class="table-btn ll-link">Professional requests</a>
                        </div>
                    @endif

                    <div class="ll-card">
                        <div class="ll-row between">
                            <div class="ll-counts">
                                <span><strong>{{ $catalogLessonCount }}</strong> {{ \Illuminate\Support\Str::plural('lesson', $catalogLessonCount) }}</span>
                                <span><strong>{{ $questionCount }}</strong> quiz {{ \Illuminate\Support\Str::plural('question', $questionCount) }}</span>
                                <span><strong>{{ $skill->levels->count() }}</strong> of {{ count($levelNames) }} levels used</span>
                                
                            </div>
                            <div class="ll-actions">
                                <a href="{{ route('admin.skills.edit', $skill) }}" class="table-btn ll-link">Edit skill</a>
                                <a href="{{ route('admin.skills.lessons.create', $skill) }}" class="settings-save-btn ll-link">+ Add lesson</a>
                            </div>
                        </div>
                        @if($skill->description)<p style="margin-top:10px;">{{ $skill->description }}</p>@endif
                        <p class="ll-muted">Learners take lessons in order, easiest level first. A lesson unlocks when the previous quiz is passed.</p>
                    </div>

                    @foreach($levelNames as $name)
                        @php($level = $skill->levels->firstWhere('name', $name))
                        @php($catalogLessons = $level ? $level->lessons->whereNull('author_id')->sortBy('order')->values() : collect())

                        <div class="ll-card ll-level-block">
                            <div class="ll-row between">
                                <div class="ll-row">
                                    <span class="ll-level-pill {{ $name }}">{{ ucfirst($name) }}</span>
                                    <span class="ll-muted">{{ $catalogLessons->count() }} {{ \Illuminate\Support\Str::plural('lesson', $catalogLessons->count()) }}</span>
                                </div>
                                <a href="{{ route('admin.skills.lessons.create', [$skill, 'level' => $name]) }}" class="table-btn ll-link">+ Add {{ $name }} lesson</a>
                            </div>

                            @forelse($catalogLessons as $lesson)
                                <div class="ll-lesson">
                                    <div>
                                        <strong>{{ $loop->iteration }}. {{ $lesson->title }}</strong>
                                        @if(($lesson->content_type ?? 'text') !== 'text')<span class="ll-badge">{{ ucfirst($lesson->content_type) }}</span>@endif
                                        <div class="ll-muted">
                                            {{ $lesson->questions->count() }} quiz {{ \Illuminate\Support\Str::plural('question', $lesson->questions->count()) }}
                                            @if($lesson->questions->isEmpty())<span class="ll-badge pending">No quiz</span>@endif
                                        </div>
                                    </div>
                                    <div class="ll-actions">
                                        <a href="{{ route('admin.skills.lessons.edit', [$skill, $lesson]) }}" class="table-btn ll-link">Edit</a>
                                        <form method="POST" action="{{ route('admin.skills.lessons.delete', [$skill, $lesson]) }}" onsubmit="return confirm('Delete this lesson and its quiz?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="delete-btn">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <p class="ll-muted ll-empty-level">No {{ $name }} lessons yet.</p>
                            @endforelse
                        </div>
                    @endforeach

                    @if($memberLessons->isNotEmpty())
                        {{-- Members' own lessons are NOT part of the catalog: they stay in their own tab. --}}
                        <div class="ll-card ll-row between">
                            <p class="ll-muted" style="margin:0">
                                {{ $memberLessons->count() }} {{ \Illuminate\Support\Str::plural('lesson', $memberLessons->count()) }} written by users for this skill are kept separate from this catalog.
                            </p>
                            <a href="{{ route('admin.lessons') }}" class="table-btn ll-link">Open User lessons</a>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </main>
</div>

</body>
</html>
