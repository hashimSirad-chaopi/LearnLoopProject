```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>LearnLoop - Settings</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

<div class="admin-container">

    <!-- =========================================
         SIDEBAR
         ========================================= -->
    <aside class="sidebar">

        <div class="sidebar-top">

            <div class="logo">
                LearnLoop
            </div>

            <nav class="sidebar-nav">

                <a href="{{ route('admin.home') }}"
                   class="nav-item">
                    Dashboard
                </a>

                <a href="{{ route('admin.users') }}"
                   class="nav-item">
                    User Management
                </a>

                <a href="{{ route('admin.listings') }}"
                   class="nav-item">
                    Listings
                </a>

                <a href="{{ route('admin.reports') }}"
                   class="nav-item">
                    Reports
                </a>

                <a href="{{ route('admin.settings') }}"
                   class="nav-item active">
                    Settings
                </a>

            </nav>

        </div>


        <!-- =====================================
             LOGOUT
             ===================================== -->
        <div class="sidebar-bottom">

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button type="submit" class="logout-btn">
                    Log Out
                </button>
            </form>

        </div>

    </aside>


    <!-- =========================================
         MAIN CONTENT
         ========================================= -->
    <main class="main-content">


        <!-- =====================================
             TOP HEADER
             ===================================== -->
        <header class="top-header">

            <div class="header-left">

                <span class="header-label">
                    ADMIN PANEL
                </span>

                <h1>
                    Settings
                </h1>

                <p>
                    Manage your administrator account and system preferences.
                </p>

            </div>

        </header>


        <!-- =====================================
             SETTINGS CONTENT
             ===================================== -->
        <section class="settings-page">

            <div class="settings-scroll-area">


                <!-- =================================
                     ADMIN ACCOUNT
                     ================================= -->
                <div class="settings-card">

                    <div class="settings-card-header">

                        <span class="section-label">
                            ACCOUNT
                        </span>

                        <h2>
                            Admin Account
                        </h2>

                        <p>
                            Update the administrator account information.
                        </p>

                    </div>


                    <form class="settings-form">

                        <div class="settings-field">

                            <label for="admin-name">
                                Name
                            </label>

                            <input
                                id="admin-name"
                                type="text"
                                value="Administrator"
                            >

                        </div>


                        <div class="settings-field">

                            <label for="admin-email">
                                Email
                            </label>

                            <input
                                id="admin-email"
                                type="email"
                                value="admin@learnloop.com"
                            >

                        </div>


                        <div class="settings-actions">

                            <button
                                type="button"
                                class="settings-save-btn"
                            >
                                Save Changes
                            </button>

                        </div>

                    </form>

                </div>


                <!-- =================================
                     CHANGE PASSWORD
                     ================================= -->
                <div class="settings-card">

                    <div class="settings-card-header">

                        <span class="section-label">
                            SECURITY
                        </span>

                        <h2>
                            Change Password
                        </h2>

                        <p>
                            Update the password used to access the admin account.
                        </p>

                    </div>


                    <form class="settings-form">

                        <div class="settings-field">

                            <label for="current-password">
                                Current Password
                            </label>

                            <input
                                id="current-password"
                                type="password"
                                placeholder="Enter current password"
                            >

                        </div>


                        <div class="settings-field">

                            <label for="new-password">
                                New Password
                            </label>

                            <input
                                id="new-password"
                                type="password"
                                placeholder="Enter new password"
                            >

                        </div>


                        <div class="settings-field">

                            <label for="confirm-password">
                                Confirm New Password
                            </label>

                            <input
                                id="confirm-password"
                                type="password"
                                placeholder="Confirm new password"
                            >

                        </div>


                        <div class="settings-actions">

                            <button
                                type="button"
                                class="settings-save-btn"
                            >
                                Update Password
                            </button>

                        </div>

                    </form>

                </div>


                <!-- =================================
                     SYSTEM PREFERENCES
                     ================================= -->
                <div class="settings-card">

                    <div class="settings-card-header">

                        <span class="section-label">
                            PREFERENCES
                        </span>

                        <h2>
                            System Preferences
                        </h2>

                        <p>
                            Control notifications and administrative alerts.
                        </p>

                    </div>


                    <!-- EMAIL NOTIFICATIONS -->
                    <div class="settings-option">

                        <div>

                            <h3>
                                Email Notifications
                            </h3>

                            <p>
                                Receive important administrative notifications
                                through email.
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


                    <!-- NEW LISTING NOTIFICATIONS -->
                    <div class="settings-option">

                        <div>

                            <h3>
                                New Listing Notifications
                            </h3>

                            <p>
                                Receive notifications when a new listing is
                                submitted by a user.
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


            </div>

        </section>

    </main>

</div>

</body>
</html>
```
