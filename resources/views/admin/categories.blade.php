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
<<<<<<< HEAD
            
@if (session('success'))
    <div role="status"
         style="padding:12px 16px; margin-bottom:16px; border-radius:8px;
                background:#ecfdf5; color:#166534;">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div role="alert"
         style="padding:12px 16px; margin-bottom:16px; border-radius:8px;
                background:#fef2f2; color:#991b1b;">
        <strong>Please check the following:</strong>
        <ul style="margin:8px 0 0 20px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
=======
            <div class="ll-scroll">
                <div class="ll-page">
                    @include('partials.flash')
                    @include('partials.catalog-tabs')
>>>>>>> 7128c7a35fdb01d62338eda557a779da41476150

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
<<<<<<< HEAD

                <div class="user-tools">

                    <form method="POST"
      action="{{ route('admin.categories.store') }}"
      style="display:flex; flex-direction:column; gap:10px; flex:1;">
    @csrf

    <input
        type="text"
        name="name"
        value="{{ old('name') }}"
        placeholder="Category name"
        class="user-search"
        aria-label="Category name"
        required
    >

    <textarea
        name="description"
        rows="3"
        placeholder="Describe the skills that belong in this category"
        aria-label="Category description"
        required
        style="padding:8px; border:1px solid #ccc; border-radius:6px;"
    >{{ old('description') }}</textarea>

    <button type="submit" class="table-btn">Add Category</button>
</form>

                </div>
               
                

            </div>

           

            <div class="users-scroll-area">

                <div class="users-table-container">
                                        
                    

                    <table class="users-table">

                        <thead>
    <tr>
        <th>Category</th>
        <th>Description</th>
        <th>Actions</th>
    </tr>
</thead>

                        
<tbody>
    @forelse ($categories as $category)
        <tr>
            <td>
                <strong>{{ $category->name }}</strong>
            </td>

            <td>
                <p style="margin:0; line-height:1.6; max-width:520px;
                          white-space:normal; overflow-wrap:anywhere;">
                    {{ $category->description ?: 'No description provided.' }}
                </p>
            </td>

            <td>
                <div class="action-buttons" style="flex-wrap:wrap; gap:8px;">
                    <details>
                        <summary class="table-btn"
                                 style="cursor:pointer; display:inline-block;">
                            Edit
                        </summary>

                        <form method="POST"
                              action="{{ route('admin.categories.update', $category->id) }}"
                              style="display:flex; flex-direction:column; gap:10px;
                                     min-width:240px; max-width:360px; margin-top:10px;">
                            @csrf
                            @method('PUT')

                            <label for="category-name-{{ $category->id }}">
                                Category name
                            </label>

                            <input
                                id="category-name-{{ $category->id }}"
                                type="text"
                                name="name"
                                value="{{ $category->name }}"
                                maxlength="255"
                                required
                            >

                            <label for="category-description-{{ $category->id }}">
                                Description
                            </label>

                            <textarea
                                id="category-description-{{ $category->id }}"
                                name="description"
                                rows="4"
                                maxlength="2000"
                                required
                            >{{ $category->description }}</textarea>

                            <button type="submit" class="table-btn">
                                Save Changes
                            </button>
                        </form>
                    </details>

                    <form method="POST"
                          action="{{ route('admin.categories.delete', $category->id) }}"
                          onsubmit="return confirm('Delete this category?');">
                        @csrf
                        @method('DELETE')

                        <button type="submit" class="delete-btn">
                            Delete
                        </button>
                    </form>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="3">No categories yet.</td>
        </tr>
    @endforelse
</tbody>

                    </table>

                </div>

            </div>

=======
            </div>
>>>>>>> 7128c7a35fdb01d62338eda557a779da41476150
        </section>
    </main>
</div>

</body>
</html>
