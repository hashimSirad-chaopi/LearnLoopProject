@extends('layouts.admin')

@section('title', 'Messages')
@section('page-title', 'Message Moderation')
@section('page-subtitle', 'Review conversations between LearnLoop members.')

@section('content')

<section class="admin-page">

    <!-- SEARCH -->
    <div class="admin-toolbar">

        <div class="dashboard-heading">
            <div>
                <span class="section-label">MESSAGES</span>
                <h2>All Messages</h2>
                <p>{{ $messages->total() }} message{{ $messages->total() === 1 ? '' : 's' }} across every exchange.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.messages.index') }}" class="inline-add-form">
            <input type="search" name="q" value="{{ $search }}" placeholder="Search message text...">
            <button type="submit" class="btn-primary">Search</button>
        </form>

    </div>


    <!-- MESSAGES TABLE -->
    <div class="admin-table-wrap">

        <table class="admin-table">
            <thead>
                <tr>
                    <th>From</th>
                    <th>To</th>
                    <th>Exchange</th>
                    <th>Message</th>
                    <th>Sent</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($messages as $message)
                    <tr>
                        <td>{{ $message->sender->name ?? 'Deleted user' }}</td>
                        <td>{{ $message->recipient->name ?? 'Deleted user' }}</td>
                        <td>
                            @if ($message->exchange)
                                <a href="{{ route('admin.exchanges.show', $message->exchange) }}" class="cell-link">
                                    #{{ $message->exchange->id }}
                                </a>
                            @else
                                <span class="cell-muted">&mdash;</span>
                            @endif
                        </td>
                        <td>{{ Str::limit($message->message_text, 80) }}</td>
                        <td class="cell-muted">{{ $message->created_at->diffForHumans() }}</td>
                        <td>
                            <span class="status-badge {{ $message->read_at ? 'status-completed' : 'status-pending' }}">
                                {{ $message->read_at ? 'Read' : 'Unread' }}
                            </span>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.messages.destroy', $message) }}"
                                  onsubmit="return confirm('Remove this message?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-small btn-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="table-empty">
                            No messages{{ $search !== '' ? ' matching "' . $search . '"' : '' }} found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    </div>

    <div class="admin-pagination">
        {{ $messages->links('vendor.pagination.admin') }}
    </div>

</section>

@endsection
