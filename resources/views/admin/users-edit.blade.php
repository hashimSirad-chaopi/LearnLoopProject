<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - Edit User</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div class="admin-container">

      @include('partials.admin-sidebar')

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <span class="header-label">ADMIN PANEL</span>
                <h1>Edit User</h1>
                <p>Update this user's account details.</p>
            </div>
        </header>

        <section class="users-page">
            <form class="settings-form" method="POST" action="{{ route('admin.users.update', $user->id) }}">
                @csrf
                @method('PUT')

                <div class="settings-field">
                    <label for="name">Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}">
                    @error('name') <p style="color:red;font-size:13px;">{{ $message }}</p> @enderror
                </div>

                <div class="settings-field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}">
                    @error('email') <p style="color:red;font-size:13px;">{{ $message }}</p> @enderror
                </div>

                <div class="settings-field">
                    <label for="password">Reset Password (optional)</label>
                    <input id="password" name="password" type="password" minlength="8" placeholder="Leave blank to keep current password (min 8 characters)">
                    @error('password') <p style="color:red;font-size:13px;">{{ $message }}</p> @enderror
                </div>

                <div class="settings-field">
                    <label for="role">Role</label>
                    <select id="role" name="role">
                        <option value="user" {{ $user->role === 'user' ? 'selected' : '' }}>User</option>
                        <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
                    </select>
                </div>

                <div class="settings-actions">
                    <a href="{{ route('admin.users') }}" class="table-btn">Cancel</a>
                    <button type="submit" class="settings-save-btn">Save Changes</button>
                </div>
            </form>
        </section>
    </main>
</div>

</body>
</html>