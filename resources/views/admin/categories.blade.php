<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - Categories</title>
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
                <h1>Categories</h1>
                <p>Group skills into categories. Listings use the category of their skill.</p>
            </div>
        </header>

        <section class="users-page">
            <div class="ll-scroll">
                <div class="ll-page">
                    @include('partials.flash')
                    @include('partials.catalog-tabs')

                    <div class="ll-toolbar">
                        <form method="GET" action="{{ route('admin.categories') }}" class="ll-search">
                            <input type="text" name="search" value="{{ $search }}" placeholder="Search categories or skills" aria-label="Search categories or skills">
                            <button type="submit" class="table-btn">Search</button>
                            @if($search !== '')<a href="{{ route('admin.categories') }}" class="table-btn ll-link">Clear</a>@endif
                        </form>

                        <form method="POST" action="{{ route('admin.categories.store') }}" class="ll-search">
                            @csrf
                            <input type="text" name="name" value="{{ old('name') }}" placeholder="New category name" aria-label="New category name" required>
                            <button type="submit" class="settings-save-btn">+ Add category</button>
                        </form>
                    </div>
                    @error('name')<p class="ll-error">{{ $message }}</p>@enderror

                    @if($uncategorized > 0)
                        <div class="ll-note">
                            <strong>{{ $uncategorized }} {{ \Illuminate\Support\Str::plural('skill', $uncategorized) }}</strong> {{ $uncategorized === 1 ? 'has' : 'have' }} no category.
                            <a href="{{ route('admin.skills', ['category' => 'none']) }}">Review them</a> and pick a category so they appear in the right place.
                        </div>
                    @endif

                    <span class="ll-muted">{{ $categories->count() }} {{ \Illuminate\Support\Str::plural('category', $categories->count()) }}{{ $search !== '' ? ' match "'.$search.'"' : '' }}</span>

                    <div class="ll-grid wide">
                        @forelse($categories as $category)
                            @php($lessons = (int) ($lessonCounts[$category->id] ?? 0))
                            <article class="ll-card ll-cat">
                                <h3>{{ $category->name }}</h3>

                                <div class="ll-counts">
                                    <span><strong>{{ $category->skills_count }}</strong> {{ \Illuminate\Support\Str::plural('skill', $category->skills_count) }}</span>
                                    <span><strong>{{ $lessons }}</strong> {{ \Illuminate\Support\Str::plural('lesson', $lessons) }}</span>
                                    <span><strong>{{ $category->listings_count }}</strong> {{ \Illuminate\Support\Str::plural('listing', $category->listings_count) }}</span>
                                </div>

                                <div class="ll-chips">
                                    @forelse($category->skills->take(6) as $skill)
                                        <a class="ll-chip {{ $skill->status === 'inactive' ? 'muted' : '' }}" href="{{ route('admin.skills.show', $skill) }}" title="{{ $skill->status === 'inactive' ? 'Inactive' : 'Open skill' }}">{{ $skill->icon ?: '📘' }} {{ $skill->title }}</a>
                                    @empty
                                        <span class="ll-muted">No skills yet.</span>
                                    @endforelse
                                    @if($category->skills->count() > 6)
                                        <a class="ll-chip more" href="{{ route('admin.skills', ['category' => $category->id]) }}">+{{ $category->skills->count() - 6 }} more</a>
                                    @endif
                                </div>

                                <div class="ll-actions">
                                    <a href="{{ route('admin.skills', ['category' => $category->id]) }}" class="table-btn ll-link">Open skills</a>
                                    <a href="{{ route('admin.skills.create', ['category' => $category->id]) }}" class="settings-save-btn ll-link">+ Add skill</a>
                                </div>

                                <details class="ll-more">
                                    <summary>Rename or delete</summary>
                                    <form method="POST" action="{{ route('admin.categories.update', $category->id) }}" class="ll-search">
                                        @csrf @method('PUT')
                                        <input type="text" name="name" value="{{ $category->name }}" aria-label="Category name" required>
                                        <button type="submit" class="table-btn">Save</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.categories.delete', $category->id) }}"
                                          onsubmit="return confirm('Delete {{ addslashes($category->name) }}?{{ $category->skills_count > 0 ? ' Its '.$category->skills_count.' skills will stay, but have no category.' : '' }}');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="delete-btn">Delete category</button>
                                    </form>
                                    @if($category->skills_count > 0)
                                        <p class="ll-muted">Deleting keeps the skills and their lessons. They just lose this category.</p>
                                    @endif
                                </details>
                            </article>
                        @empty
                            <div class="ll-card">
                                <h3>{{ $search !== '' ? 'No categories match' : 'No categories yet' }}</h3>
                                <p>{{ $search !== '' ? 'Try a different search.' : 'Add your first category above, or run the sample seeder: php artisan db:seed --class=CategorySeeder' }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

</body>
</html>
