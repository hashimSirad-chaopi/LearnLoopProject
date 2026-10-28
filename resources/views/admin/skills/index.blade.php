<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - Skills &amp; Lessons</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.admin-filters')
</head>
<body>

<div class="admin-container">

    @include('partials.admin-sidebar')

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <span class="header-label">ADMIN PANEL</span>
                <h1>Skills &amp; Lessons</h1>
                <p>The courses users learn from in the app. Open a skill to manage its levels, lessons and quizzes.</p>
            </div>
        </header>

        <section class="users-page">
            <div class="ll-scroll">
                <div class="ll-page">
                    @include('partials.flash')
                    @include('partials.catalog-tabs')

                    <form method="GET" action="{{ route('admin.skills') }}" class="ll-toolbar">
                        <div class="ll-search">
                            <input type="text" name="search" value="{{ $search }}" placeholder="Search skills" aria-label="Search skills">
                            <select name="category" aria-label="Category">
                                <option value="">All categories</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" @selected($category === (string) $cat->id)>{{ $cat->name }}</option>
                                @endforeach
                                <option value="none" @selected($category === 'none')>No category</option>
                            </select>
                            <select name="status" aria-label="Status">
                                <option value="">Any status</option>
                                <option value="active" @selected($status === 'active')>Active</option>
                                <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                            </select>
                            <button type="submit" class="table-btn">Filter</button>
                            @if($search !== '' || $category !== '' || $status !== '')
                                <a href="{{ route('admin.skills') }}" class="table-btn ll-link">Clear</a>
                            @endif
                        </div>
                        <a href="{{ route('admin.skills.create', ctype_digit($category) ? ['category' => $category] : []) }}" class="settings-save-btn ll-link">+ Add skill</a>
                    </form>

                    <span class="ll-muted">
                        Showing {{ $total }} of {{ $allCount }} {{ \Illuminate\Support\Str::plural('skill', $allCount) }}
                    </span>

                    @forelse($groups as $categoryId => $skills)
                        <section class="ll-group">
                            <div class="ll-row between">
                                <h3 class="ll-group-title">
                                    @if($categoryId === 0) No category @else {{ $names[$categoryId] ?? 'Category' }} @endif
                                    <span class="ll-muted">({{ $skills->count() }})</span>
                                </h3>
                                @if($categoryId !== 0)
                                    <a href="{{ route('admin.skills.create', ['category' => $categoryId]) }}" class="table-btn ll-link">+ Add skill here</a>
                                @endif
                            </div>

                            <div class="ll-grid">
                                @foreach($skills as $skill)
                                    @php($lessonTotal = (int) ($lessonCounts[$skill->id] ?? 0))
                                    <article class="ll-card">
                                        <div class="ll-row between">
                                            <span class="ll-skill-icon">{{ $skill->icon ?: '📘' }}</span>
                                            <span class="ll-badge {{ $skill->status }}">{{ ucfirst($skill->status) }}</span>
                                        </div>
                                        <h3 style="margin-top:10px;">{{ $skill->title }}</h3>
                                        <p>{{ \Illuminate\Support\Str::limit($skill->description ?: 'No description yet.', 90) }}</p>
                                        <p class="ll-muted">
                                            {{ $skill->levels_count }} {{ \Illuminate\Support\Str::plural('level', $skill->levels_count) }}
                                            &middot; {{ $lessonTotal }} {{ \Illuminate\Support\Str::plural('lesson', $lessonTotal) }}
                                            @if($lessonTotal === 0)<span class="ll-badge pending">Empty</span>@endif
                                        </p>
                                        <div class="ll-actions">
                                            <a href="{{ route('admin.skills.show', $skill) }}" class="table-btn ll-link">Manage lessons</a>
                                            <a href="{{ route('admin.skills.edit', $skill) }}" class="table-btn ll-link">Edit</a>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @empty
                        <div class="ll-card">
                            <h3>{{ $allCount === 0 ? 'No skills yet' : 'No skills match' }}</h3>
                            @if($allCount === 0)
                                <p>Add one with “+ Add skill”, or load a ready-made catalog:</p>
                                <p><code>php artisan db:seed --class=SampleCatalogSeeder</code></p>
                            @else
                                <p>Try a different search, or <a href="{{ route('admin.skills') }}">clear the filters</a>.</p>
                            @endif
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    </main>
</div>

</body>
</html>
