```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>LearnLoop - User Management</title>

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
                   class="nav-item active">
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
                   class="nav-item">
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
                    User Management
                </h1>

                <p>
                    Manage LearnLoop users and their accounts.
                </p>

            </div>

        </header>


        <!-- =====================================
             USERS CONTENT
             ===================================== -->
        <section class="users-page">


            <!-- =================================
                 TOOLBAR
                 ================================= -->
            <div class="users-toolbar">

                <div class="dashboard-heading">

                    <div>

                        <span class="section-label">
                            USERS
                        </span>

                        <h2>
                            All Users
                        </h2>

                        <p>
                            View and manage registered LearnLoop users.
                        </p>

                    </div>

                </div>


                <!-- SEARCH -->
                <div class="user-tools">

                    <input
                        type="search"
                        class="user-search"
                        placeholder="Search users..."
                        aria-label="Search users"
                    >

                </div>

            </div>


            <!-- =================================
                 SCROLLABLE USERS AREA
                 ================================= -->
            <div class="users-scroll-area">

                <div class="users-table-container">

                    <table class="users-table">

                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>


                        <tbody>

                            @forelse($users as $user)

                                <tr>

                                    <!-- NAME -->
                                    <td>
                                        {{ $user->name }}
                                    </td>


                                    <!-- EMAIL -->
                                    <td>
                                        {{ $user->email }}
                                    </td>


                                    <!-- ROLE -->
                                    <td>

                                        <span class="role-badge">
                                            {{ $user->role ?? 'User' }}
                                        </span>

                                    </td>


                                    <!-- STATUS -->
                                    <td>

                                        <span class="status-badge active-status">
                                            Active
                                        </span>

                                    </td>


                                    <!-- ACTIONS -->
                                    <td>

                                        <div class="action-buttons">

                                            <button
                                                type="button"
                                                class="table-btn"
                                            >
                                                View
                                            </button>


                                            <form
                                                method="POST"
                                                action="{{ route('admin.users.delete', $user->id) }}"
                                                onsubmit="return confirm('Are you sure you want to delete this user?');"
                                            >

                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    class="delete-btn"
                                                >
                                                    Delete
                                                </button>

                                            </form>

                                        </div>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="5" class="empty-users">
                                        No users found.
                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </section>

    </main>

</div>

</body>
</html>
```
