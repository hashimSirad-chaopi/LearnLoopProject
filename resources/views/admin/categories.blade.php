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

    <aside class="sidebar">
        <div class="sidebar-top">
            <div class="logo">LearnLoop</div>
            <nav class="sidebar-nav">
                <a href="{{ route('admin.home') }}" class="nav-item">Dashboard</a>
                <a href="{{ route('admin.users') }}" class="nav-item">User Management</a>
                <a href="{{ route('admin.listings') }}" class="nav-item active">Listings</a>
                <a href="{{ route('admin.exchanges') }}" class="nav-item">Exchange Monitoring</a>
                <a href="{{ route('admin.reports') }}" class="nav-item">Reports</a>
                <a href="{{ route('admin.settings') }}" class="nav-item">Settings</a>
            </nav>
        </div>
        <div class="sidebar-bottom">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="logout-btn">Log Out</button>
            </form>
        </div>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <span class="header-label">ADMIN PANEL</span>
                <h1>Manage Categories</h1>
                <p>Add or remove skill categories used across listings.</p>
            </div>
        </header>

        <section class="users-page">
            <form method="POST" action="{{ route('admin.categories.store') }}" style="display:flex; gap:10px; margin-bottom:25px;">
                @csrf
                <input type="text" name="name" placeholder="New category name" style="flex:1; padding:10px; border-radius:8px; border:1px solid #ccc;">
                <button type="submit" class="settings-save-btn" style="width:auto; padding:10px 20px;">Add</button>
            </form>
            @error('name') <p style="color:red;font-size:13px;">{{ $message }}</p> @enderror

            <table class="users-table">
                <thead>
                    <tr><th>Category</th><th>Action</th></tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                       <tr>
    <td>
        <form method="POST" action="{{ route('admin.categories.update', $category->id) }}" style="display:flex; gap:8px;">
            @csrf @method('PUT')
            <input type="text" name="name" value="{{ $category->name }}" style="flex:1; padding:6px; border-radius:6px; border:1px solid #ccc;">
            <button type="submit" class="table-btn">Save</button>
        </form>
    </td>
    <td>
        <form method="POST" action="{{ route('admin.categories.delete', $category->id) }}" onsubmit="return confirm('Delete this category?');">
            @csrf @method('DELETE')
            <button type="submit" class="delete-btn">Delete</button>
        </form>
    </td>
</tr>
                    @empty
                        <tr><td colspan="2">No categories yet.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <a href="{{ route('admin.listings') }}" class="table-btn" style="text-decoration:none; display:inline-block; margin-top:20px;">← Back to Listings</a>
        </section>
    </main>
</div>

</body>
</html>