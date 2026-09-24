<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - Add User</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div class="admin-container">

    <aside class="sidebar">
        <div class="sidebar-top">
            <div class="logo">LearnLoop</div>
            <nav class="sidebar-nav">
                <a href="{{ route('admin.home') }}" class="nav-item">Dashboard</a>
                <a href="{{ route('admin.users') }}" class="nav-item active">User Management</a>
                <a href="{{ route('admin.listings') }}" class="nav-item">Listings</a>
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
                <h1>Add User</h1>
                <p>Create a new LearnLoop account.</p>
            </div>
        </header>

        <section class="users-page">
            <form class="settings-form" method="POST" action="{{ route('admin.users.store') }}">
                @csrf

                <div class="settings-field">
                    <label for="name">Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}">
                    @error('name') <p style="color:red;font-size:13px;">{{ $message }}</p> @enderror
                </div>

                <div class="settings-field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}">
                    @error('email') <p style="color:red;font-size:13px;">{{ $message }}</p> @enderror
                </div>

                <div class="settings-field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password">
                    @error('password') <p style="color:red;font-size:13px;">{{ $message }}</p> @enderror
                </div>

                <div class="settings-field">
                    <label for="role">Role</label>
                    <select id="role" name="role">
                        <option value="user" {{ old('role') === 'user' ? 'selected' : '' }}>User</option>
                       
                    </select>
                </div>

                <div class="settings-actions">
                    <a href="{{ route('admin.users') }}" class="table-btn">Cancel</a>
                    <button type="submit" class="settings-save-btn">Create User</button>
                </div>
            </form>
        </section>
    </main>
</div>

</body>
</html>