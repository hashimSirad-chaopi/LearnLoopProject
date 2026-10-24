@extends('layouts.admin')

@section('title', 'Notifications')
@section('page-title', 'Notification Log')
@section('page-subtitle', 'Every notification sent to LearnLoop members, plus manual sends.')

@section('content')

<section class="admin-page">

    <!-- SEND MANUAL NOTIFICATION -->
    <div class="detail-section">

        <h2 class="section-title">Send a notification</h2>

        <form method="POST" action="{{ route('admin.notifications.store') }}" class="action-form" style="max-width: 560px;">
            @csrf

            <label for="user_search">Recipient</label>
            <input
                type="text"
                id="user_search"
                list="user_options"
                class="user-search-input"
                placeholder="Type a name or email to search..."
                autocomplete="off"
                required
            >
            <datalist id="user_options">
                @foreach ($users as $user)
                    <option data-user-id="{{ $user->id }}" value="{{ $user->name }} ({{ $user->email }})"></option>
                @endforeach
            </datalist>
            <input type="hidden" name="user_id" id="user_id">
            <p class="field-hint" id="user_search_hint"></p>

            <label for="title">Title</label>
            <input type="text" name="title" id="title" placeholder="e.g. Scheduled maintenance" required>

            <label for="message">Message</label>
            <textarea name="message" id="message" rows="3" required></textarea>

            <button type="submit" class="btn-primary">Send notification</button>
        </form>

        <script>
            (function () {
                const search = document.getElementById('user_search');
                const hidden = document.getElementById('user_id');
                const hint   = document.getElementById('user_search_hint');
                const options = Array.from(document.querySelectorAll('#user_options option'));
                const form = search.closest('form');

                function matchTypedValue() {
                    const match = options.find(o => o.value === search.value);

                    if (match) {
                        hidden.value = match.dataset.userId;
                        hint.textContent = '';
                        search.classList.remove('field-invalid');
                    } else {
                        hidden.value = '';
                        hint.textContent = search.value.trim() === ''
                            ? ''
                            : 'No matching user \u2014 pick one from the suggestions.';
                        search.classList.toggle('field-invalid', search.value.trim() !== '');
                    }
                }

                search.addEventListener('input', matchTypedValue);

                form.addEventListener('submit', function (e) {
                    matchTypedValue();
                    if (!hidden.value) {
                        e.preventDefault();
                        hint.textContent = 'Select a user from the suggestions before sending.';
                        search.classList.add('field-invalid');
                        search.focus();
                    }
                });
            })();
        </script>

    </div>


    <!-- FILTERS -->
    <div class="dashboard-heading">
        <div>
            <span class="section-label">LOG</span>
            <h2>All Notifications</h2>
            <p>{{ $notifications->total() }} sent in total.</p>
        </div>
    </div>

    <div class="filter-row">
        <span class="filter-row-label">Status</span>
        <div class="filter-tabs">
            <a href="{{ route('admin.notifications.index', ['type' => $currentType, 'read' => 'all']) }}"
               class="filter-tab {{ $currentStatus === 'all' ? 'active' : '' }}">All</a>
            <a href="{{ route('admin.notifications.index', ['type' => $currentType, 'read' => 'unread']) }}"
               class="filter-tab {{ $currentStatus === 'unread' ? 'active' : '' }}">Unread</a>
            <a href="{{ route('admin.notifications.index', ['type' => $currentType, 'read' => 'read']) }}"
               class="filter-tab {{ $currentStatus === 'read' ? 'active' : '' }}">Read</a>
        </div>
    </div>

    <div class="filter-row">
        <span class="filter-row-label">Type</span>
        <div class="filter-tabs">
            @foreach ($typeOptions as $option)
                <a href="{{ route('admin.notifications.index', ['type' => $option, 'read' => $currentStatus]) }}"
                   class="filter-tab {{ $currentType === $option ? 'active' : '' }}">
                    {{ ucfirst(str_replace('_', ' ', $option)) }}
                </a>
            @endforeach
        </div>
    </div>


    <!-- TABLE -->
    <div class="admin-table-wrap">

        <table class="admin-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Type</th>
                    <th>Title</th>
                    <th>Message</th>
                    <th>Sent</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($notifications as $notification)
                    <tr>
                        <td>{{ $notification->user->name ?? 'Deleted user' }}</td>
                        <td>
                            <span class="state-badge status-admin">
                                {{ ucfirst(str_replace('_', ' ', $notification->type)) }}
                            </span>
                        </td>
                        <td>{{ $notification->title }}</td>
                        <td>{{ Str::limit($notification->message, 60) }}</td>
                        <td class="cell-muted">{{ $notification->created_at->diffForHumans() }}</td>
                        <td>
                            <span class="state-badge {{ $notification->read_at ? 'status-completed' : 'status-pending' }}">
                                {{ $notification->read_at ? 'Read' : 'Unread' }}
                            </span>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.notifications.destroy', $notification) }}"
                                  onsubmit="return confirm('Delete this notification?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-small btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="table-empty">No notifications match this filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    </div>

    <div class="admin-pagination">
        {{ $notifications->links('vendor.pagination.admin') }}
    </div>

</section>

@endsection
