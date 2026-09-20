```blade
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
                   class="nav-item active">
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
                    Reports
                </h1>

                <p>
                    Review and manage reports submitted by LearnLoop users.
                </p>

            </div>

        </header>


        <!-- =====================================
             REPORTS CONTENT
             ===================================== -->
        <section class="reports-page">


            <!-- =================================
                 REPORTS TOOLBAR
                 ================================= -->
            <div class="reports-toolbar">

                <div class="dashboard-heading">

                    <div>

                        <span class="section-label">
                            REPORTS
                        </span>

                        <h2>
                            Reported Issues
                        </h2>

                        <p>
                            Monitor reported users and listings that require
                            administrative attention.
                        </p>

                    </div>

                </div>


                <!-- SEARCH + FILTER -->
                <div class="report-tools">

                    <input
                        type="search"
                        class="report-search"
                        placeholder="Search reports..."
                        aria-label="Search reports"
                    >
                    <a href="{{ route('admin.reports.create') }}" class="settings-save-btn" style="text-decoration:none; display:inline-block; padding:10px 18px;">+ File Report</a>
                    <select
                        class="report-filter"
                        aria-label="Filter reports"
                    >
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

            </div>


            <!-- =================================
                 SCROLLABLE REPORTS AREA
                 ================================= -->
            <div class="reports-scroll-area">

                <div class="reports-container">


                    <!-- =================================
                         REPORT 1
                         ================================= -->
                    @forelse($reports as $report)
        <article class="report-card">
        <header class="report-header">
            <div class="report-icon">!</div>
            <div class="report-title">
                <h3>{{ $report->title }}</h3>
                <span>Reported by {{ $report->reporter->name ?? 'Unknown' }}</span>
            </div>
            <span class="report-status {{ $report->status }}">{{ ucfirst($report->status) }}</span>
        </header>

        <div class="report-content">
            <div class="report-detail">
                <span class="detail-label">Reported User</span>
                <strong>{{ $report->reportedUser->name ?? 'N/A' }}</strong>
            </div>
            <div class="report-detail">
                <span class="detail-label">Reported Listing</span>
                <strong>{{ $report->listing->title ?? 'N/A' }}</strong>
            </div>
            <div class="report-detail">
                <span class="detail-label">Reason</span>
                <p>{{ $report->reason }}</p>
            </div>
        </div>

        <footer class="report-footer">
            <span>Requires administrative review</span>
            <div class="report-actions">
    @if($report->status === 'pending')
        <form method="POST" action="{{ route('admin.reports.resolve', $report->id) }}">
            @csrf @method('PATCH')
            <button type="submit" class="report-resolve-btn">Resolve</button>
        </form>
        <form method="POST" action="{{ route('admin.reports.dismiss', $report->id) }}">
            @csrf @method('PATCH')
            <button type="submit" class="report-dismiss-btn">Dismiss</button>
        </form>
    @endif
    <form method="POST" action="{{ route('admin.reports.delete', $report->id) }}" onsubmit="return confirm('Delete this report?');">
        @csrf @method('DELETE')
        <button type="submit" class="delete-btn">Delete</button>
    </form>
</div>
        </footer>
    </article>
    @empty
    <p>No reports found.</p>
        @endforelse


            </div>

            </div>

        </section>

    </main>

</div>

</body>
</html>
```
