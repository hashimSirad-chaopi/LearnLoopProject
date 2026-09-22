<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - Add Exchange</title>
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
                <h1>Add Exchange</h1>
                <p>Manually create a skill exchange record.</p>
            </div>
        </header>

        <section class="users-page">
            <form class="settings-form" method="POST" action="{{ route('admin.exchanges.store') }}">
                @csrf

                <div class="settings-field">
                    <label for="provider_id">Provider</label>
                    <select id="provider_id" name="provider_id">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('provider_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="settings-field">
                    <label for="learner_id">Learner</label>
                    <select id="learner_id" name="learner_id">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('learner_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="settings-field">
                    <label for="skill_offered">Skill Offered</label>
                    <input id="skill_offered" name="skill_offered" type="text" value="{{ old('skill_offered') }}">
                    @error('skill_offered') <p style="color:red;font-size:13px;">{{ $message }}</p> @enderror
                </div>

                <div class="settings-field">
                    <label for="skill_wanted">Skill Wanted</label>
                    <input id="skill_wanted" name="skill_wanted" type="text" value="{{ old('skill_wanted') }}">
                    @error('skill_wanted') <p style="color:red;font-size:13px;">{{ $message }}</p> @enderror
                </div>

                <div class="settings-field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="pending">Pending</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                        <option value="disputed">Disputed</option>
                    </select>
                </div>

                <div class="settings-actions">
                    <a href="{{ route('admin.exchanges') }}" class="table-btn">Cancel</a>
                    <button type="submit" class="settings-save-btn">Create Exchange</button>
                </div>
            </form>
        </section>
    </main>
</div>

</body>
</html>