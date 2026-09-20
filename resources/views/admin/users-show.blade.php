<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - View User</title>
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
                <h1>User Details</h1>
                <p>Viewing account information.</p>
            </div>
        </header>

        <section class="users-page">
            <div class="settings-form">
                <div class="settings-field">
                    <label>Name</label>
                    <p>{{ $user->name }}</p>
                </div>
                <div class="settings-field">
                    <label>Email</label>
                    <p>{{ $user->email }}</p>
                </div>
                <div class="settings-field">
                    <label>Role</label>
                    <p>{{ ucfirst($user->role) }}</p>
                </div>
                <div class="settings-field">
                    <label>Joined</label>
                    <p>{{ $user->created_at->format('F j, Y') }}</p>
                </div>

                <div class="settings-actions">
                    <a href="{{ route('admin.users') }}" class="table-btn">Back</a>
                    <a href="{{ route('admin.users.edit', $user->id) }}" class="settings-save-btn" style="text-decoration:none; display:inline-block; text-align:center;">Edit</a>
                </div>
            </div>
        </section>
    </main>
</div>

</body>
</html>