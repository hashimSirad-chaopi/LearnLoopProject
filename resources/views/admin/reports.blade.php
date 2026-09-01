<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">


<title>LearnLoop - Reports</title>

@vite(['resources/css/app.css', 'resources/js/app.js'])


</head>

<body>

<div class="admin-container">


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

<a href="{{ route('admin.reports') }}" class="nav-item active">
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
            <h1>Reports</h1>

            <p>
                Review and manage reports submitted by users.
            </p>
        </div>

    </header>


    <!-- CONTENT -->
    <section class="dashboard-content">

        <div class="dashboard-heading">

            <h2>Reported Issues</h2>

            <p>
                Monitor reported users and listings that require
                administrative attention.
            </p>

        </div>


        <!-- REPORT FILTERS -->
        <div class="report-tools">

            <input
                type="text"
                class="report-search"
                placeholder="Search reports..."
            >

            <select class="report-filter">

                <option value="all">
                    All Reports
                </option>

                <option value="pending">
                    Pending
                </option>

                <option value="resolved">
                    Resolved
                </option>

                <option value="dismissed">
                    Dismissed
                </option>

            </select>

        </div>


        <!-- REPORTS -->
        <div class="reports-container">


            <!-- REPORT 1 -->
            <article class="report-card">

                <div class="report-header">

                    <div class="report-icon">
                        !
                    </div>

                    <div class="report-title">

                        <h3>
                            Inappropriate Listing
                        </h3>

                        <span>
                            Reported by Harold Caño
                        </span>

                    </div>

                    <span class="report-status pending">
                        Pending
                    </span>

                </div>


                <div class="report-content">

                    <div class="report-detail">

                        <span class="detail-label">
                            Reported User
                        </span>

                        <strong>
                            User Account
                        </strong>

                    </div>

                    <div class="report-detail">

                        <span class="detail-label">
                            Reported Listing
                        </span>

                        <strong>
                            Mathematics Tutoring
                        </strong>

                    </div>

                    <div class="report-detail">

                        <span class="detail-label">
                            Reason
                        </span>

                        <p>
                            The listing contains inappropriate
                            or misleading information.
                        </p>

                    </div>

                </div>


                <div class="report-footer">

                    <span>
                        Submitted recently
                    </span>

                    <div class="report-actions">

                        <button class="report-view-btn">
                            View
                        </button>

                        <button class="report-resolve-btn">
                            Resolve
                        </button>

                        <button class="report-dismiss-btn">
                            Dismiss
                        </button>

                    </div>

                </div>

            </article>


            <!-- REPORT 2 -->
            <article class="report-card">

                <div class="report-header">

                    <div class="report-icon">
                        !
                    </div>

                    <div class="report-title">

                        <h3>
                            Suspicious User Activity
                        </h3>

                        <span>
                            Reported by Myra Balaba
                        </span>

                    </div>

                    <span class="report-status pending">
                        Pending
                    </span>

                </div>


                <div class="report-content">

                    <div class="report-detail">

                        <span class="detail-label">
                            Reported User
                        </span>

                        <strong>
                            User Account
                        </strong>

                    </div>

                    <div class="report-detail">

                        <span class="detail-label">
                            Type
                        </span>

                        <strong>
                            User
                        </strong>

                    </div>

                    <div class="report-detail">

                        <span class="detail-label">
                            Reason
                        </span>

                        <p>
                            Suspicious behavior was observed
                            during a tutoring interaction.
                        </p>

                    </div>

                </div>


                <div class="report-footer">

                    <span>
                        Submitted recently
                    </span>

                    <div class="report-actions">

                        <button class="report-view-btn">
                            View
                        </button>

                        <button class="report-resolve-btn">
                            Resolve
                        </button>

                        <button class="report-dismiss-btn">
                            Dismiss
                        </button>

                    </div>

                </div>

            </article>


            <!-- REPORT 3 -->
            <article class="report-card">

                <div class="report-header">

                    <div class="report-icon">
                        !
                    </div>

                    <div class="report-title">

                        <h3>
                            Spam Listing
                        </h3>

                        <span>
                            Reported by Richard Formoso
                        </span>

                    </div>

                    <span class="report-status resolved">
                        Resolved
                    </span>

                </div>


                <div class="report-content">

                    <div class="report-detail">

                        <span class="detail-label">
                            Reported Listing
                        </span>

                        <strong>
                            Web Development Help
                        </strong>

                    </div>

                    <div class="report-detail">

                        <span class="detail-label">
                            Type
                        </span>

                        <strong>
                            Listing
                        </strong>

                    </div>

                    <div class="report-detail">

                        <span class="detail-label">
                            Reason
                        </span>

                        <p>
                            Duplicate or spam content was
                            submitted multiple times.
                        </p>

                    </div>

                </div>


                <div class="report-footer">

                    <span>
                        Submitted recently
                    </span>

                    <div class="report-actions">

                        <button class="report-view-btn">
                            View
                        </button>

                        <button class="report-resolve-btn">
                            Resolve
                        </button>

                        <button class="report-dismiss-btn">
                            Dismiss
                        </button>

                    </div>

                </div>

            </article>


        </div>

    </section>

</main>
```

</div>

</body>
</html>
