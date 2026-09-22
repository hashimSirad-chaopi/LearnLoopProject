<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - Exchange Monitoring</title>
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
                <a href="{{ route('admin.listings') }}" class="nav-item">Listings</a>
                <a href="{{ route('admin.exchanges') }}" class="nav-item active">Exchange Monitoring</a>
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
                <h1>Exchange Monitoring</h1>
                <p>Track all ongoing and completed skill exchanges between users.</p>
            </div>
        </header>

        <section class="users-page">
            <a href="{{ route('admin.exchanges.create') }}" class="settings-save-btn" style="text-decoration:none; display:inline-block; padding:10px 18px; margin-bottom:15px;">+ Add Exchange</a>
            <div class="table-wrapper">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Provider</th>
                            <th>Learner</th>
                            <th>Skill Exchanged</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($exchanges as $exchange)
                            <tr>
                                <td>#EX-{{ str_pad($exchange->id, 3, '0', STR_PAD_LEFT) }}</td>
                                <td>{{ $exchange->provider->name ?? 'N/A' }}</td>
                                <td>{{ $exchange->learner->name ?? 'N/A' }}</td>
                                <td>{{ $exchange->skill_offered }} ↔ {{ $exchange->skill_wanted }}</td>
                                <td><span class="status-badge active-status">{{ ucfirst($exchange->status) }}</span></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="{{ route('admin.exchanges.show', $exchange->id) }}" class="table-btn" style="text-decoration:none; display:inline-block;">View</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6">No exchanges found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

</body>
</html>