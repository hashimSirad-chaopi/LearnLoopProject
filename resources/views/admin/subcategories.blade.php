
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - Subcategories</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>

/* Allow the document to grow and scroll naturally */
html,
body {
    height: auto !important;
    min-height: 100%;
    overflow-x: hidden !important;
    overflow-y: auto !important;
}

/* Override the fixed admin layout height on this page only */
.admin-container {
    height: auto !important;
    min-height: 100vh;
    overflow: visible !important;
}

.subcategories-main {
    height: auto !important;
    min-height: 100vh;
    overflow: visible !important;
}

/* The main culprit: remove the fixed-height, clipped content panel */
.subcategories-main .users-page {
    display: block !important;
    height: auto !important;
    min-height: 100vh;
    overflow: visible !important;
    padding-top: var(--ll-header-height);
}

/* Let the entire subcategory list expand vertically */
.subcategories-main .users-scroll-area {
    display: block !important;
    height: auto !important;
    max-height: none !important;
    min-height: 0 !important;
    flex: none !important;
    overflow-x: auto !important;
    overflow-y: visible !important;
}

/* Don't clip the table vertically */
.subcategories-main .users-table-container {
    height: auto !important;
    max-height: none !important;
    overflow: visible !important;
}
</style>
</head>
<body>

<div class="admin-container">

    @include('partials.admin-sidebar')

    <main class="main-content subcategories-main">
        <header class="top-header">
            <div class="header-left">
                <span class="header-label">ADMIN PANEL</span>
                <h1>Manage Subcategories</h1>
                <p>Manage specific skills available under each main category.</p>
            </div>
        </header>

        <section class="users-page">

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

            <div class="users-toolbar">
                <div class="dashboard-heading">
                    <div>
                        <span class="section-label">SKILL CATALOG</span>
                        <h2>Add a Subcategory</h2>
                        <p>Choose a parent category and define a specific skill.</p>
                    </div>
                </div>

                <div class="user-tools">
                    <form method="POST"
                          action="{{ route('admin.subcategories.store') }}"
                          style="display:flex; flex-direction:column; gap:10px; width:100%;">
                        @csrf

                        <label for="new-category-id">Main category</label>
                        <select id="new-category-id" name="category_id" required>
                            <option value="">Choose a category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>

                        <label for="new-subcategory-name">Skill name</label>
                        <input id="new-subcategory-name"
                               type="text"
                               name="name"
                               value="{{ old('name') }}"
                               maxlength="150"
                               placeholder="e.g. Vue.js Development"
                               required>

                        <label for="new-subcategory-description">Description</label>
                        <textarea id="new-subcategory-description"
                                  name="description"
                                  rows="3"
                                  maxlength="2000"
                                  placeholder="Describe the skill and its learning topics"
                                  required>{{ old('description') }}</textarea>

                        <button type="submit" class="table-btn">
                            Add Subcategory
                        </button>
                    </form>
                </div>
            </div>

            <div class="dashboard-heading" style="margin:24px 0 12px;">
                <div>
                    <span class="section-label">EXISTING SKILLS</span>
                    <h2>All Subcategories ({{ $subcategories->count() }})</h2>
                </div>
            </div>

            <div class="users-scroll-area">
                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Main Category</th>
                                <th>Specific Skill</th>
                                <th>Description</th>
                                <th>Listings</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($subcategories as $subcategory)
                                <tr>
                                    <td>
                                        {{ $subcategory->category?->name ?? 'Unknown category' }}
                                    </td>

                                    <td>
                                        <strong>{{ $subcategory->name }}</strong>
                                    </td>

                                    <td>
                                        <div style="max-width:360px; white-space:normal;
                                                    overflow-wrap:anywhere; line-height:1.5;">
                                            {{ $subcategory->description }}
                                        </div>
                                    </td>

                                    <td>{{ $subcategory->listings_count }}</td>

                                    <td>
                                        <div style="display:flex; flex-direction:column;
                                                    align-items:flex-start; gap:10px;">

                                            <details>
                                                <summary class="table-btn"
                                                         style="cursor:pointer;">
                                                    Edit
                                                </summary>

                                                <form method="POST"
                                                      action="{{ route('admin.subcategories.update', $subcategory->id) }}"
                                                      style="display:flex; flex-direction:column;
                                                             gap:8px; min-width:240px;
                                                             max-width:340px; margin-top:10px;">
                                                    @csrf
                                                    @method('PUT')

                                                    <label for="category-{{ $subcategory->id }}">
                                                        Main category
                                                    </label>
                                                    <select id="category-{{ $subcategory->id }}"
                                                            name="category_id" required>
                                                        @foreach ($categories as $categoryOption)
                                                            <option value="{{ $categoryOption->id }}"
                                                                {{ $subcategory->category_id == $categoryOption->id ? 'selected' : '' }}>
                                                                {{ $categoryOption->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>

                                                    <label for="name-{{ $subcategory->id }}">
                                                        Skill name
                                                    </label>
                                                    <input id="name-{{ $subcategory->id }}"
                                                           type="text"
                                                           name="name"
                                                           value="{{ $subcategory->name }}"
                                                           maxlength="150"
                                                           required>

                                                    <label for="description-{{ $subcategory->id }}">
                                                        Description
                                                    </label>
                                                    <textarea id="description-{{ $subcategory->id }}"
                                                              name="description"
                                                              rows="4"
                                                              maxlength="2000"
                                                              required>{{ $subcategory->description }}</textarea>

                                                    <button type="submit" class="table-btn">
                                                        Save Changes
                                                    </button>
                                                </form>
                                            </details>

                                            <form method="POST"
                                                  action="{{ route('admin.subcategories.delete', $subcategory->id) }}"
                                                  onsubmit="return confirm('Delete this subcategory?');">
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
                                    <td colspan="5">No subcategories have been created yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </section>
    </main>
</div>

</body>
</html>