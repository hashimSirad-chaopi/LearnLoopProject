@extends('layouts.admin')

@section('title', 'Exchanges')
@section('page-title', 'Exchange Management')
@section('page-subtitle', 'Monitor skill exchanges between LearnLoop users.')

@section('content')

<section class="admin-page">

    <!-- STATUS FILTER TABS -->
    <div class="admin-toolbar">

        <div class="dashboard-heading">
            <div>
                <span class="section-label">EXCHANGES</span>
                <h2>All Exchanges</h2>
                <p>Filter by status, then open one to review its sessions and messages.</p>
            </div>
        </div>

        <div class="filter-tabs">
            @foreach ($statusOptions as $option)
                <a href="{{ route('admin.exchanges.index', ['status' => $option]) }}"
                   class="filter-tab {{ $currentStatus === $option ? 'active' : '' }}">
                    {{ ucfirst($option) }}
                    <span class="filter-count">
                        {{ $option === 'all' ? $counts->sum() : ($counts[$option] ?? 0) }}
                    </span>
                </a>
            @endforeach
        </div>

    </div>


    <!-- EXCHANGES TABLE -->
    <div class="admin-table-wrap">

        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Provider</th>
                    <th>Learner</th>
                    <th>Skills</th>
                    <th>Status</th>
                    <th>Activity</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($exchanges as $exchange)
                    <tr>
                        <td class="cell-id">#{{ $exchange->id }}</td>

                        <td>
                            @if ($exchange->provider)
                                <a href="{{ route('admin.users.show', $exchange->provider) }}" class="cell-link">
                                    {{ $exchange->provider->name }}
                                </a>
                            @else
                                <span class="cell-muted">Deleted user</span>
                            @endif
                        </td>

                        <td>
                            @if ($exchange->learner)
                                <a href="{{ route('admin.users.show', $exchange->learner) }}" class="cell-link">
                                    {{ $exchange->learner->name }}
                                </a>
                            @else
                                <span class="cell-muted">Deleted user</span>
                            @endif
                        </td>

                        <td class="cell-skills">
                            <span class="skill-chip">{{ $exchange->skill_offered }}</span>
                            <span class="skill-arrow">&rarr;</span>
                            <span class="skill-chip">{{ $exchange->skill_wanted }}</span>
                        </td>

                        <td>
                            <span class="status-badge status-{{ $exchange->status }}">
                                {{ ucfirst($exchange->status) }}
                            </span>
                        </td>

                        <td class="cell-muted">
                            {{ $exchange->schedules_count }} sessions &middot;
                            {{ $exchange->messages_count }} msgs &middot;
                            {{ $exchange->ratings_count }} ratings
                        </td>

                        <td class="cell-muted">{{ $exchange->created_at->diffForHumans() }}</td>

                        <td>
                            <div class="row-actions">
                                <a href="{{ route('admin.exchanges.show', $exchange) }}" class="btn-small">
                                    View
                                </a>

                                @if (! in_array($exchange->status, ['cancelled', 'completed', 'resolved']))
                                    <form method="POST"
                                          action="{{ route('admin.exchanges.cancel', $exchange) }}"
                                          onsubmit="return confirm('Cancel exchange #{{ $exchange->id }}?');">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn-small btn-danger">Cancel</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="table-empty">
                            No exchanges{{ $currentStatus !== 'all' ? ' with status "' . $currentStatus . '"' : '' }} yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    </div>

    <div class="admin-pagination">
        {{ $exchanges->links('vendor.pagination.admin') }}
    </div>

</section>

@endsection
