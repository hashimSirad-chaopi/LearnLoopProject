<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - File a Report</title>
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
                <a href="{{ route('admin.reports') }}" class="nav-item active">Reports</a>
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
                <h1>File a Report</h1>
                <p>Create a new report on behalf of a user.</p>
            </div>
        </header>

        <section class="users-page">
            <form class="settings-form" method="POST" action="{{ route('admin.reports.store') }}">
                @csrf

                <div class="settings-field">
                    <label for="title">Title</label>
                    <input id="title" name="title" type="text" value="{{ old('title') }}">
                    @error('title') <p style="color:red;font-size:13px;">{{ $message }}</p> @enderror
                </div>

                <div class="settings-field">
                    <label for="reported_by">Reported By</label>
                    <select id="reported_by" name="reported_by">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('reported_by') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="settings-field">
                    <label for="reported_user_id">Reported User</label>
                    <select id="reported_user_id" name="reported_user_id">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('reported_user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="settings-field">
                    <label for="listing_id">Related Listing (optional)</label>
                    <select id="listing_id" name="listing_id">
                        <option value="">None</option>
                        @foreach($listings as $listing)
                            <option value="{{ $listing->id }}" {{ old('listing_id') == $listing->id ? 'selected' : '' }}>{{ $listing->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="settings-field">
                    <label for="reason">Reason</label>
                    <textarea id="reason" name="reason" rows="4">{{ old('reason') }}</textarea>
                    @error('reason') <p style="color:red;font-size:13px;">{{ $message }}</p> @enderror
                </div>

                <div class="settings-field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="resolved" {{ old('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="dismissed" {{ old('status') === 'dismissed' ? 'selected' : '' }}>Dismissed</option>
                    </select>
                </div>

                <div class="settings-actions">
                    <a href="{{ route('admin.reports') }}" class="table-btn">Cancel</a>
                    <button type="submit" class="settings-save-btn">File Report</button>
                </div>
            </form>
        </section>
    </main>
</div>

</body>
</html>