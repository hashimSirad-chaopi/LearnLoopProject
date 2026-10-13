<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - Categories</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div class="admin-container">

      @include('partials.admin-sidebar')

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <span class="header-label">ADMIN PANEL</span>
                <h1>Manage Categories</h1>
                <p>Add or remove skill categories used across listings.</p>
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

                        <span class="section-label">
                            CATEGORIES
                        </span>

                        <h2>
                            All Categories
                        </h2>

                        <p>
                            Manage the categories available to listings.
                        </p>

                    </div>

                </div>

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

        </section>
    </main>
</div>

</body>
</html>