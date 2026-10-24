<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - Activity Log</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.admin-filters')
</head>
<body>

<div class="admin-container">

    @include('partials.admin-sidebar')

    <main class="main-content" data-live>
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
                    <div class="ll-toolbar">
                        <form method="GET" action="{{ route('admin.activity') }}" class="ll-search">
                            <select name="type" aria-label="Filter by type" onchange="this.form.submit()">
                                <option value="">All activity</option>
                                @foreach($types as $key => $label)
                                    <option value="{{ $key }}" {{ $type === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <input type="search" name="search" value="{{ $search }}" placeholder="Search the log" aria-label="Search the activity log">
                            <button type="submit" class="table-btn">Search</button>
                            @if($search !== '' || $type !== '')
                                <a href="{{ route('admin.activity') }}" class="table-btn ll-link">Clear</a>
                            @endif
                        </form>
                    </div>
                    <span class="ll-muted">
                        {{ number_format($logs->total()) }} {{ \Illuminate\Support\Str::plural('entry', $logs->total()) }}
                        @if($type !== '') &middot; {{ $types[$type] }} only @endif
                        @if($search !== '') &middot; matching &ldquo;{{ $search }}&rdquo; @endif
                    </span>

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
                                <tr><td colspan="4">{{ $search !== '' || $type !== '' ? 'No activity matches these filters.' : 'No activity recorded yet.' }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $logs->links('vendor.pagination.admin') }}
        </section>
    </main>
</div>

</body>
</html>