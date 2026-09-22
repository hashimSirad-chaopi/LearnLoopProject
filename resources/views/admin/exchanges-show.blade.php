<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - Exchange Details</title>
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
                <h1>Exchange #EX-{{ str_pad($exchange->id, 3, '0', STR_PAD_LEFT) }}</h1>
                <p>Review and manage this exchange.</p>
            </div>
        </header>

        <section class="users-page">
            <div class="settings-form">
                <div class="settings-field">
                    <label>Provider</label>
                    <p>{{ $exchange->provider->name ?? 'N/A' }}</p>
                </div>
                <div class="settings-field">
                    <label>Learner</label>
                    <p>{{ $exchange->learner->name ?? 'N/A' }}</p>
                </div>
                <div class="settings-field">
                    <label>Skill Offered</label>
                    <p>{{ $exchange->skill_offered }}</p>
                </div>
                <div class="settings-field">
                    <label>Skill Wanted</label>
                    <p>{{ $exchange->skill_wanted }}</p>
                </div>

                <form method="POST" action="{{ route('admin.exchanges.update', $exchange->id) }}">
                    @csrf
                    @method('PATCH')
                    <div class="settings-field">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="pending" {{ $exchange->status === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="ongoing" {{ $exchange->status === 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                            <option value="completed" {{ $exchange->status === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="disputed" {{ $exchange->status === 'disputed' ? 'selected' : '' }}>Disputed</option>
                        </select>
                    </div>
                    <div class="settings-actions">
                        <a href="{{ route('admin.exchanges') }}" class="table-btn">Back</a>
                        <button type="submit" class="settings-save-btn">Update Status</button>
                    </div>
                </form>

                <form method="POST" action="{{ route('admin.exchanges.delete', $exchange->id) }}" onsubmit="return confirm('Delete this exchange?');" style="margin-top:15px;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="delete-btn">Delete Exchange</button>
                </form>
            </div>
        </section>
    </main>
</div>

</body>
</html>