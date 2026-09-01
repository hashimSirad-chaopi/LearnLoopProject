<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

```
<title>LearnLoop - User Management</title>

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

<a href="{{ route('admin.users') }}" class="nav-item active">
    User Management
</a>

<a href="{{ route('admin.listings') }}" class="nav-item">
    Listings
</a>

<a href="{{ route('admin.reports') }}" class="nav-item">
    Reports
</a>

<a href="{{ route('admin.settings') }}" class="nav-item">
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
            <h1>User Management</h1>
            <p>Manage LearnLoop users and their accounts.</p>
        </div>

    </header>


    <!-- CONTENT -->
    <section class="dashboard-content">

        <!-- PAGE TITLE -->
        <div class="dashboard-heading">

            <h2>Users</h2>

            <p>
                View and manage registered users.
            </p>

        </div>


        <!-- SEARCH -->
        <div class="user-tools">

            <input
                type="text"
                class="user-search"
                placeholder="Search users..."
            >

        </div>


        <!-- USER TABLE -->
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

                    <tr>
                        <td>Harold Caño</td>

                        <td>
                            harold@example.com
                        </td>

                        <td>
                            <span class="role-badge">
                                Admin
                            </span>
                        </td>

                        <td>
                            <span class="status-badge active-status">
                                Active
                            </span>
                        </td>

                        <td>
                            <button class="table-btn">
                                View
                            </button>
                        </td>
                    </tr>


                    <tr>
                        <td>Myra Balaba</td>

                        <td>
                            myra@example.com
                        </td>

                        <td>
                            <span class="role-badge">
                                User
                            </span>
                        </td>

                        <td>
                            <span class="status-badge active-status">
                                Active
                            </span>
                        </td>

                        <td>
                            <button class="table-btn">
                                View
                            </button>
                        </td>
                    </tr>


                    <tr>
                        <td>Richard Formoso</td>

                        <td>
                            richard@example.com
                        </td>

                        <td>
                            <span class="role-badge">
                                User
                            </span>
                        </td>

                        <td>
                            <span class="status-badge active-status">
                                Active
                            </span>
                        </td>

                        <td>
                            <button class="table-btn">
                                View
                            </button>
                        </td>
                    </tr>


                    <tr>
                        <td>Christian Jay Ayunan</td>

                        <td>
                            christian@example.com
                        </td>

                        <td>
                            <span class="role-badge">
                                User
                            </span>
                        </td>

                        <td>
                            <span class="status-badge inactive-status">
                                Inactive
                            </span>
                        </td>

                        <td>
                            <button class="table-btn">
                                View
                            </button>
                        </td>
                    </tr>

                </tbody>

            </table>

        </div>

    </section>

</main>
```

</div>

</body>
</html>
