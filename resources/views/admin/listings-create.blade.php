<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - Add Listing</title>
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
                <h1>Add Listing</h1>
                <p>Create a new skill listing.</p>
            </div>
        </header>

        <section class="listing-page">
            <form class="settings-form" method="POST" action="{{ route('admin.listings.store') }}">
                @csrf

                <div class="settings-field">
                    <label for="user_id">User</label>
                    <select id="user_id" name="user_id">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="settings-field">
                    <label for="title">Title</label>
                    <input id="title" name="title" type="text" value="{{ old('title') }}">
                    @error('title') <p style="color:red;font-size:13px;">{{ $message }}</p> @enderror
                </div>

                <div class="settings-field">
    <label for="category">Category</label>
    <select id="category" name="category">
        @foreach(\App\Models\Category::all() as $cat)
            <option value="{{ $cat->name }}" {{ (old('category', '')) === $cat->name ? 'selected' : '' }}>{{ $cat->name }}</option>
        @endforeach
    </select>
</div>

                <div class="settings-field">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4">{{ old('description') }}</textarea>
                    @error('description') <p style="color:red;font-size:13px;">{{ $message }}</p> @enderror
                </div>

                <div class="settings-field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div class="settings-actions">
                    <a href="{{ route('admin.listings') }}" class="table-btn">Cancel</a>
                    <button type="submit" class="settings-save-btn">Create Listing</button>
                </div>
            </form>
        </section>
    </main>
</div>

</body>
</html>
