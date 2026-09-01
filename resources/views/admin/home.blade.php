<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

```
<title>LearnLoop - Admin Dashboard</title>

@vite(['resources/css/app.css', 'resources/js/app.js'])
```

</head>

<body>

```
<div class="admin-container">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="logo">
            LearnLoop
        </div>

        <nav class="sidebar-nav">

            <a href="{{ route('admin.home') }}" class="nav-item active">
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

        <!-- TOP HEADER -->
        <header class="top-header">

            <div>
                <h1>Dashboard</h1>
                <p>Welcome to LearnLoop Admin</p>
            </div>

        </header>


        <!-- DASHBOARD -->
        <section class="dashboard-content">

            <div class="dashboard-heading">
                <h2>Overview</h2>
                <p>System statistics and activity</p>
            </div>


            <!-- STATISTICS -->
            <div class="cards">

                <div class="card">
                    <h3>Total Users</h3>
                    <h2>{{ \App\Models\User::count() }}</h2>
                </div>

                <div class="card">
                    <h3>Active Tutors</h3>
                    <h2>10</h2>
                </div>

                <div class="card">
                    <h3>Active Sessions</h3>
                    <h2>3</h2>
                </div>

                <div class="card">
                    <h3>Average Rating</h3>
                    <h2>3.45</h2>
                </div>

            </div>


            <!-- RECENT ACTIVITY -->
            <div class="recent-activity">

                <h2>Recent Activity</h2>

                <div class="activity-item">
                    <p>No recent activity available.</p>
                </div>

            </div>

        </section>

    </main>

</div>
```

</body>
</html>
