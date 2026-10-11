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

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <span class="header-label">ADMIN PANEL</span>
                <h1>Exchange Monitoring</h1>
                <p>Track all ongoing and completed skill exchanges between users.</p>
            </div>
        </header>

        <section class="users-page">

            <div class="users-toolbar">

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
                    <th>Skill Exchanged</th>
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
                        <td>{{ $exchange->skill_offered }} ↔ {{ $exchange->skill_wanted }}</td>
                        <td><span class="status-badge active-status">{{ ucfirst($exchange->status) }}</span></td>
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

</div>

        </section>
    </main>
</div>

</body>
</html>