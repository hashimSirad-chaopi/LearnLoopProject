<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - Exchange Monitoring</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div class="admin-container">

       @include('partials.admin-sidebar')

    <main class="main-content" data-live>
        <header class="top-header">
            <div class="header-left">
                <span class="header-label">ADMIN PANEL</span>
                <h1>Exchange Monitoring</h1>
                <p>Track all ongoing and completed skill exchanges between users.</p>
            </div>
        </header>

        <section class="users-page">

            <div class="users-toolbar">
                @include('partials.flash')
@include('partials.user-filter', ['what' => 'exchanges'])


                <div class="dashboard-heading">

                    <div>

                        <span class="section-label">
                            EXCHANGES
                        </span>

                        <h2>
                            All Exchanges
                        </h2>

                        <p>
                            View and manage all recorded skill exchanges.
                        </p>

                    </div>

                </div>

                <div class="ll-tabs" style="margin-bottom:12px;">
                    <a class="ll-tab {{ $statusFilter === 'all' ? 'active' : '' }}" href="{{ route('admin.exchanges', array_filter(['user' => request('user')])) }}">All ({{ $counts->sum() }})</a>
                    <a class="ll-tab {{ $statusFilter === 'disputed' ? 'active' : '' }}" href="{{ route('admin.exchanges', array_filter(['user' => request('user'), 'status' => 'disputed'])) }}">Disputed ({{ $counts['disputed'] ?? 0 }})</a>
                    <a class="ll-tab {{ $statusFilter === 'pending' ? 'active' : '' }}" href="{{ route('admin.exchanges', array_filter(['user' => request('user'), 'status' => 'pending'])) }}">Pending ({{ $counts['pending'] ?? 0 }})</a>
                    <a class="ll-tab {{ $statusFilter === 'ongoing' ? 'active' : '' }}" href="{{ route('admin.exchanges', array_filter(['user' => request('user'), 'status' => 'ongoing'])) }}">Ongoing ({{ $counts['ongoing'] ?? 0 }})</a>
                    <a class="ll-tab {{ $statusFilter === 'completed' ? 'active' : '' }}" href="{{ route('admin.exchanges', array_filter(['user' => request('user'), 'status' => 'completed'])) }}">Completed ({{ $counts['completed'] ?? 0 }})</a>
                    <a class="ll-tab {{ $statusFilter === 'cancelled' ? 'active' : '' }}" href="{{ route('admin.exchanges', array_filter(['user' => request('user'), 'status' => 'cancelled'])) }}">Cancelled ({{ $counts['cancelled'] ?? 0 }})</a>
                </div>

                <div class="user-tools">

                    <a href="{{ route('admin.exchanges.create') }}" class="settings-save-btn" style="text-decoration:none;">+ Add Exchange</a>

                </div>

            </div>

           <div class="users-scroll-area">

    <div class="users-table-container">

        <table class="users-table exchanges-table">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Provider</th>
                    <th>Learner</th>
                    <th>Skills (provider teaches ↔ learner teaches)</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse($exchanges as $exchange)
                    <tr>
                        <td>#EX-{{ str_pad($exchange->id, 3, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ $exchange->provider->name ?? 'N/A' }}</td>
                        <td>{{ $exchange->learner->name ?? 'N/A' }}</td>
                        <td>
                            {{ $exchange->skillPair() }}
                            @if($exchange->status === 'disputed' && $exchange->dispute_reason)
                                <div class="ll-muted">{{ \Illuminate\Support\Str::limit($exchange->dispute_reason, 70) }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="ll-badge {{ $exchange->status }}">{{ ucfirst($exchange->status) }}</span>
                            @if($exchange->isLearnerOnly())<span class="ll-badge learner-only">Learner only</span>@endif
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="{{ route('admin.exchanges.show', $exchange->id) }}" class="table-btn" style="text-decoration:none;">View</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">No exchanges found.</td></tr>
                @endforelse
            </tbody>

        </table>

    </div>

    {{ $exchanges->links('vendor.pagination.admin') }}

</div>

        </section>
    </main>
</div>

</body>
</html>