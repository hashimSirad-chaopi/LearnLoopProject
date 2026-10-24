<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - Notifications</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div class="admin-container">

    @include('partials.admin-sidebar')

    <main class="main-content" data-live>
        <header class="top-header">
            <div class="header-left">
                <span class="header-label">ADMIN PANEL</span>
                <h1>Notifications</h1>
                <p>Your own alerts are kept apart from what members receive in the app.</p>
            </div>
        </header>

        <section class="users-page">
            <div class="ll-scroll">
                <div class="ll-page">
                    @include('partials.flash')

                    <div class="ll-tabs">
                        <a class="ll-tab {{ $tab === 'attention' ? 'active' : '' }}" href="{{ route('admin.notifications', ['tab' => 'attention']) }}">For you ({{ $attentionTotal }})</a>
                        <a class="ll-tab {{ $tab === 'sent' ? 'active' : '' }}" href="{{ route('admin.notifications', ['tab' => 'sent']) }}">Sent to members ({{ $sentCount }})</a>
                        <a class="ll-tab {{ $tab === 'activity' ? 'active' : '' }}" href="{{ route('admin.notifications', ['tab' => 'activity']) }}">Member activity ({{ $activityCount }})</a>
                    </div>

                    @if($tab === 'attention')
                        <p class="ll-muted">Work waiting for an administrator. These update by themselves as you clear them.</p>
                        <div class="ll-grid">
                            @foreach($attention as $item)
                                <article class="ll-card">
                                    <div class="ll-row between">
                                        <h3>{{ $item['label'] }}</h3>
                                        <span class="ll-badge {{ $item['count'] > 0 ? 'pending' : 'completed' }}">{{ $item['count'] }}</span>
                                    </div>
                                    <p class="ll-muted">{{ $item['hint'] }}</p>
                                    <div class="ll-actions">
                                        <a class="settings-save-btn ll-link" href="{{ $item['url'] }}">{{ $item['count'] > 0 ? 'Review' : 'Open' }}</a>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        @if($tab === 'sent')
                            <div class="ll-card">
                                <h3>Send a notice</h3>
                                <p class="ll-muted">Shows up in the member's app under the Admin tab.</p>
                                <form method="POST" action="{{ route('admin.notifications.store') }}" class="ll-form">
                                    @csrf
                                    <div>
                                        <label for="user_id">Recipient</label>
                                        <select name="user_id" id="user_id" required>
                                            <option value="">Choose a member</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="title">Title</label>
                                        <input type="text" name="title" id="title" maxlength="150" value="{{ old('title') }}" required>
                                    </div>
                                    <div>
                                        <label for="message">Message</label>
                                        <textarea name="message" id="message" rows="3" maxlength="500" required>{{ old('message') }}</textarea>
                                    </div>
                                    <div><button type="submit" class="settings-save-btn">Send notice</button></div>
                                </form>
                            </div>
                        @else
                            <p class="ll-muted">Alerts members got from each other or the app (a coach adding a lesson, a lesson confirmed). They appear under Activity in the app, not Admin.</p>
                        @endif

                        <div class="ll-tabs">
                            <a class="ll-tab {{ $status === 'all' ? 'active' : '' }}" href="{{ route('admin.notifications', ['tab' => $tab, 'read' => 'all']) }}">All</a>
                            <a class="ll-tab {{ $status === 'unread' ? 'active' : '' }}" href="{{ route('admin.notifications', ['tab' => $tab, 'read' => 'unread']) }}">Unread</a>
                            <a class="ll-tab {{ $status === 'read' ? 'active' : '' }}" href="{{ route('admin.notifications', ['tab' => $tab, 'read' => 'read']) }}">Read</a>
                        </div>

                        <div class="ll-card">
                            <table class="ll-table">
                                <thead>
                                    <tr>
                                        <th>Member</th>
                                        <th>Title</th>
                                        <th>Message</th>
                                        <th>Sent</th>
                                        <th>Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse($notifications as $notification)
                                    <tr>
                                        <td>{{ $notification->user?->name ?? 'Deleted user' }}</td>
                                        <td><strong>{{ $notification->title }}</strong></td>
                                        <td>{{ \Illuminate\Support\Str::limit($notification->message, 70) }}</td>
                                        <td>{{ $notification->created_at->diffForHumans() }}</td>
                                        <td><span class="ll-badge {{ $notification->read_at ? 'completed' : 'pending' }}">{{ $notification->read_at ? 'Read' : 'Unread' }}</span></td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.notifications.delete', $notification) }}" onsubmit="return confirm('Delete this notification?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="delete-btn">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="ll-muted">Nothing here.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{ $notifications->links('vendor.pagination.admin') }}
                    @endif
                </div>
            </div>
        </section>
    </main>
</div>

</body>
</html>
