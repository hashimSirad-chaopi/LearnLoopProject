<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - Activity Log</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div class="admin-container">

    @include('partials.admin-sidebar')

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <span class="header-label">ADMIN PANEL</span>
                <h1>Activity Log</h1>
                <p>A record of every administrative action.</p>
            </div>
        </header>

        <section class="users-page">
            <div class="users-scroll-area">
                <div class="users-table-container">
                    <table class="users-table activity-table">
                        <thead>
                            <tr>
                                <th>When</th>
                                <th>Admin</th>
                                <th>Action</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                <tr>
                                    <td>{{ $log->created_at->format('M j, g:i A') }}</td>
                                    <td>{{ $log->admin->name ?? 'Deleted admin' }}</td>
                                    <td>{{ $log->action }}</td>
                                    <td>{{ $log->description }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4">No activity recorded yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $logs->links('pagination::simple-bootstrap-5') }}
        </section>
    </main>
</div>

</body>
</html>