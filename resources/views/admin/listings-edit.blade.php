<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - Edit Listing</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div class="admin-container">

       @include('partials.admin-sidebar')

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <span class="header-label">ADMIN PANEL</span>
                <h1>Edit Listing</h1>
                <p>Update this listing's details.</p>
            </div>
        </header>

        <section class="listing-page">
            <form class="settings-form" method="POST" action="{{ route('admin.listings.update', $listing->id) }}">
                @csrf
                @method('PUT')

                <div class="settings-field">
                    <label for="user_id">User</label>
                    <select id="user_id" name="user_id">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ $listing->user_id == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="settings-field">
                    <label for="title">Title</label>
                    <input id="title" name="title" type="text" value="{{ old('title', $listing->title) }}">
                    @error('title') <p style="color:red;font-size:13px;">{{ $message }}</p> @enderror
                </div>

                    
<div class="settings-field">
    <label for="category_id">Category</label>

    <select id="category_id" name="category_id" required>
        <option value="">Choose a category</option>

        @foreach ($categories as $cat)
            <option value="{{ $cat->id }}"
                {{ old('category_id', $listing->category_id) == $cat->id ? 'selected' : '' }}>
                {{ $cat->name }}
            </option>
        @endforeach
    </select>

    @error('category_id')
        <p role="alert" style="color:red;font-size:13px;">
            {{ $message }}
        </p>
    @enderror
</div>

<div class="settings-field">
    <label for="subcategory_id">Specific Skill</label>

    <select id="subcategory_id" name="subcategory_id" required>
        <option value="">Choose a specific skill</option>

        @foreach ($categories as $cat)
            @foreach ($cat->subcategories as $sub)
                <option
                    value="{{ $sub->id }}"
                    data-category-id="{{ $cat->id }}"
                    {{ old('subcategory_id', $listing->subcategory_id) == $sub->id ? 'selected' : '' }}>
                    {{ $sub->name }}
                </option>
            @endforeach
        @endforeach
    </select>

    @error('subcategory_id')
        <p role="alert" style="color:red;font-size:13px;">
            {{ $message }}
        </p>
    @enderror
</div>

<div class="settings-field">
    <label for="level">Difficulty Level</label>

    <select id="level" name="level" required>
        <option value="">Choose a difficulty level</option>

        <option value="beginner"
            {{ old('level', $listing->level) === 'beginner' ? 'selected' : '' }}>
            Beginner
        </option>

        <option value="intermediate"
            {{ old('level', $listing->level) === 'intermediate' ? 'selected' : '' }}>
            Intermediate
        </option>

        <option value="advanced"
            {{ old('level', $listing->level) === 'advanced' ? 'selected' : '' }}>
            Advanced
        </option>
    </select>

    @error('level')
        <p role="alert" style="color:red;font-size:13px;">
            {{ $message }}
        </p>
    @enderror
</div>

                <div class="settings-field">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4">{{ old('description', $listing->description) }}</textarea>
                    @error('description') <p style="color:red;font-size:13px;">{{ $message }}</p> @enderror
                </div>

                <div class="settings-field">
                 
<div class="settings-field">
    <label>Moderation Status</label>

    <p style="margin:0; padding:10px 12px; border-radius:8px;
              background:#f3f4f6; color:#374151;">
        {{ ucfirst($listing->status) }}
    </p>

    <small style="display:block; margin-top:6px; color:#69766e;">
        To change this status, use the moderation actions on the Listings page.
    </small>
</div>

                    <select id="status" name="status">
                        
                        <option value="active" {{ $listing->status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $listing->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div class="settings-actions">
                    <a href="{{ route('admin.listings') }}" class="table-btn">Cancel</a>
                    <button type="submit" class="settings-save-btn">Save Changes</button>
                </div>
            </form>
        </section>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const categorySelect = document.getElementById('category_id');
    const subcategorySelect = document.getElementById('subcategory_id');

    if (!categorySelect || !subcategorySelect) {
        return;
    }

    function filterSubcategories(resetSelection = false) {
        const categoryId = categorySelect.value;
        const selectedSubcategory = subcategorySelect.value;
        let selectedIsValid = false;

        Array.from(subcategorySelect.options).forEach(function (option) {
            if (option.value === '') {
                option.hidden = false;
                option.disabled = false;
                return;
            }

            const matches = option.dataset.categoryId === categoryId;

            option.hidden = !matches;
            option.disabled = !matches;

            if (matches && option.value === selectedSubcategory) {
                selectedIsValid = true;
            }
        });

        if (resetSelection || !selectedIsValid) {
            subcategorySelect.value = '';
        }
    }

    categorySelect.addEventListener('change', function () {
        filterSubcategories(true);
    });

    filterSubcategories();
});
</script>
</body>
</html>
