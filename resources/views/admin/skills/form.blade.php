@php($editing = $skill->exists)
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - {{ $editing ? 'Edit' : 'Add' }} Skill</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div class="admin-container">

    @include('partials.admin-sidebar')

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <span class="header-label">ADMIN PANEL</span>
                <h1>{{ $editing ? 'Edit Skill' : 'Add Skill' }}</h1>
                <p>A skill is one course in the Learn tab, such as "Intro to Python".</p>
            </div>
        </header>

        <section class="users-page">
            <div class="ll-scroll">
                <div class="ll-page">
                    @include('partials.flash')
                    @include('partials.crumbs', ['items' => $editing
                        ? [['Skills & lessons', route('admin.skills')], [$skill->title, route('admin.skills.show', $skill)], ['Edit']]
                        : [['Skills & lessons', route('admin.skills')], ['New skill']]])

                    <form class="ll-card ll-form" method="POST"
                          action="{{ $editing ? route('admin.skills.update', $skill) : route('admin.skills.store') }}">
                        @csrf
                        @if($editing) @method('PUT') @endif

                        <div>
                            <label for="title">Title</label>
                            <input id="title" name="title" type="text" value="{{ old('title', $skill->title) }}" required>
                        </div>

                        <div>
                            <label for="category_id">Category</label>
                            <select id="category_id" name="category_id" required>
                                <option value="">Choose a category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ (string) old('category_id', $skill->category_id) === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <p class="ll-muted">Listings use the category of their skill, so members never pick it separately.</p>
                        </div>

                        <div>
                            <label for="icon">Icon (one emoji)</label>
                            <input id="icon" name="icon" type="text" maxlength="16" value="{{ old('icon', $skill->icon) }}">
                        </div>

                        <div>
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3">{{ old('description', $skill->description) }}</textarea>
                        </div>

                        <div>
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="active" {{ old('status', $skill->status) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $skill->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>

                        <div class="settings-actions">
                            <a href="{{ $editing ? route('admin.skills.show', $skill) : route('admin.skills') }}" class="table-btn ll-link">Cancel</a>
                            <button type="submit" class="settings-save-btn">{{ $editing ? 'Save Skill' : 'Create Skill' }}</button>
                        </div>
                    </form>

                    @if($editing)
                        <form method="POST" action="{{ route('admin.skills.delete', $skill) }}"
                              onsubmit="return confirm('Delete this skill with all its levels, lessons and quizzes?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="delete-btn">Delete Skill</button>
                        </form>
                    @endif
                </div>
            </div>
        </section>
    </main>
</div>

</body>
</html>
