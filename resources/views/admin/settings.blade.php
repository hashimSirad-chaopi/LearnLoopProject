<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

```
<title>LearnLoop - Settings</title>

@vite(['resources/css/app.css', 'resources/js/app.js'])
```

</head>

<body>

<div class="admin-container">

```
<!-- SIDEBAR -->
<aside class="sidebar">

    <div class="logo">
        LearnLoop
    </div>

    <nav class="sidebar-nav">

        <a href="{{ route('admin.home') }}" class="nav-item">
    Dashboard
</a>

<a href="{{ route('admin.users') }}" class="nav-item">
    User Management
</a>

<a href="{{ route('admin.listings') }}" class="nav-item">
    Listings
</a>

<a href="{{ route('admin.reports') }}" class="nav-item">
    Reports
</a>

<a href="{{ route('admin.settings') }}" class="nav-item active">
    Settings
</a>

    </nav>

    <div class="sidebar-bottom">

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="logout-btn">
                Log Out
            </button>
        </form>

    </div>

</aside>


<!-- MAIN CONTENT -->
<main class="main-content">

    <!-- HEADER -->
    <header class="top-header">

        <div>
            <h1>Settings</h1>

            <p>
                Manage your administrator account and system preferences.
            </p>
        </div>

    </header>


    <!-- SETTINGS CONTENT -->
    <section class="settings-content">


        <!-- ACCOUNT SETTINGS -->
        <div class="settings-card">

            <div class="settings-card-header">

                <h2>Admin Account</h2>

                <p>
                    Manage your administrator account information.
                </p>

            </div>


            <div class="settings-form">

                <div class="settings-field">

                    <label for="admin-name">
                        Name
                    </label>

                    <input
                        type="text"
                        id="admin-name"
                        value="Administrator"
                    >

                </div>


                <div class="settings-field">

                    <label for="admin-email">
                        Email
                    </label>

                    <input
                        type="email"
                        id="admin-email"
                        value="admin@learnloop.com"
                    >

                </div>


                <div class="settings-actions">

                    <button class="settings-save-btn">
                        Save Changes
                    </button>

                </div>

            </div>

        </div>


        <!-- PASSWORD SETTINGS -->
        <div class="settings-card">

            <div class="settings-card-header">

                <h2>Change Password</h2>

                <p>
                    Update your administrator account password.
                </p>

            </div>


            <div class="settings-form">

                <div class="settings-field">

                    <label for="current-password">
                        Current Password
                    </label>

                    <input
                        type="password"
                        id="current-password"
                        placeholder="Enter current password"
                    >

                </div>


                <div class="settings-field">

                    <label for="new-password">
                        New Password
                    </label>

                    <input
                        type="password"
                        id="new-password"
                        placeholder="Enter new password"
                    >

                </div>


                <div class="settings-field">

                    <label for="confirm-password">
                        Confirm New Password
                    </label>

                    <input
                        type="password"
                        id="confirm-password"
                        placeholder="Confirm new password"
                    >

                </div>


                <div class="settings-actions">

                    <button class="settings-save-btn">
                        Update Password
                    </button>

                </div>

            </div>

        </div>


        <!-- SYSTEM SETTINGS -->
        <div class="settings-card">

            <div class="settings-card-header">

                <h2>System Preferences</h2>

                <p>
                    Manage basic preferences for the LearnLoop admin panel.
                </p>

            </div>


            <div class="settings-option">

                <div>

                    <h3>
                        Email Notifications
                    </h3>

                    <p>
                        Receive notifications about important reports
                        and system activity.
                    </p>

                </div>

                <label class="toggle">

                    <input
                        type="checkbox"
                        checked
                    >

                    <span class="toggle-slider"></span>

                </label>

            </div>


            <div class="settings-option">

                <div>

                    <h3>
                        New Listing Notifications
                    </h3>

                    <p>
                        Receive notifications when users create
                        new listings.
                    </p>

                </div>

                <label class="toggle">

                    <input
                        type="checkbox"
                        checked
                    >

                    <span class="toggle-slider"></span>

                </label>

            </div>


        </div>


    </section>

</main>
```

</div>

</body>
</html>
