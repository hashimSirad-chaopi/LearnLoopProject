
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
       @include('partials.admin-sidebar')


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

    <form method="GET" action="{{ route('admin.reports') }}" style="display:flex; gap:10px; flex:1; align-items:center;">
        <input
            type="search"
            name="search"
            value="{{ $search }}"
            class="report-search"
            placeholder="Search reports..."
            aria-label="Search reports"
        >

        <select name="status" class="report-filter" aria-label="Filter reports" onchange="this.form.submit()">
            <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Reports</option>
            <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="resolved" {{ $statusFilter === 'resolved' ? 'selected' : '' }}>Resolved</option>
            <option value="dismissed" {{ $statusFilter === 'dismissed' ? 'selected' : '' }}>Dismissed</option>
        </select>

        <button type="submit" class="table-btn">Search</button>
    </form>

    

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

