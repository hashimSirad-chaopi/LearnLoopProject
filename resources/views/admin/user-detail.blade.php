@extends('layouts.admin')

@section('title', $user->name)
@section('page-title', $user->name)
@section('page-subtitle', 'Full account overview for this user.')

@section('content')

<section class="admin-page">

    <a href="{{ route('admin.users') }}" class="back-link">&larr; Back to user management</a>


    <!-- PROFILE SUMMARY -->
    <div class="detail-grid">

        <div class="detail-card">
            <span class="card-label">ACCOUNT</span>
            <h3>{{ $user->name }}</h3>
            <p class="card-description">{{ $user->email }}</p>
            <p class="card-description">
                Role: <span class="status-badge status-{{ $user->role }}">{{ ucfirst($user->role) }}</span>
            </p>
            <p class="card-description">Joined {{ $user->created_at->format('j M Y') }}</p>
        </div>

        <div class="detail-card">
            <span class="card-label">REPUTATION</span>
            <h3>
                @if ($averageRating)
                    {{ number_format($averageRating, 1) }} / 5
                @else
                    No ratings yet
                @endif
            </h3>
            <p class="card-description">{{ $ratings->count() }} review{{ $ratings->count() === 1 ? '' : 's' }} received</p>
        </div>

        <div class="detail-card">
            <span class="card-label">ACTIVITY</span>
            <h3>{{ $exchanges->count() }} exchanges</h3>
            <p class="card-description">{{ $user->listings->count() }} listings posted</p>
            <p class="card-description">{{ $upcomingCount }} upcoming session{{ $upcomingCount === 1 ? '' : 's' }}</p>
        </div>

    </div>


    <!-- LISTINGS -->
    <div class="detail-section">

        <h2 class="section-title">Listings ({{ $user->listings->count() }})</h2>

        @forelse ($user->listings as $listing)
            <div class="mini-row">
                <div>
                    <strong>{{ $listing->title }}</strong>
                    <span class="cell-muted">&middot; {{ $listing->category }}</span>
                    <p class="cell-muted">{{ Str::limit($listing->description, 120) }}</p>
                </div>
                <span class="status-badge status-{{ $listing->status }}">
                    {{ ucfirst($listing->status) }}
                </span>
            </div>
        @empty
            <p class="empty-note">This user has not posted any listings.</p>
        @endforelse

    </div>


    <!-- EXCHANGES -->
    <div class="detail-section">

        <h2 class="section-title">Exchanges ({{ $exchanges->count() }})</h2>

        @forelse ($exchanges as $exchange)
            <div class="mini-row">
                <div>
                    <a href="{{ route('admin.exchanges.show', $exchange) }}" class="cell-link">
                        <strong>Exchange #{{ $exchange->id }}</strong>
                    </a>
                    <p class="cell-muted">
                        {{ $exchange->provider_id === $user->id ? 'Providing' : 'Learning' }}:
                        {{ $exchange->skill_offered }} &rarr; {{ $exchange->skill_wanted }}
                    </p>
                    <p class="cell-muted">
                        With
                        {{ $exchange->provider_id === $user->id
                            ? ($exchange->learner->name ?? 'Deleted user')
                            : ($exchange->provider->name ?? 'Deleted user') }}
                    </p>
                </div>
                <span class="status-badge status-{{ $exchange->status }}">
                    {{ ucfirst($exchange->status) }}
                </span>
            </div>
        @empty
            <p class="empty-note">This user has no exchanges.</p>
        @endforelse

    </div>


    <!-- RATINGS RECEIVED -->
    <div class="detail-section">

        <h2 class="section-title">Reviews received ({{ $ratings->count() }})</h2>

        @forelse ($ratings as $rating)
            <div class="mini-row">
                <div>
                    <strong>{{ $rating->rater->name ?? 'Deleted user' }}</strong>
                    <span class="cell-muted">{{ $rating->created_at->diffForHumans() }}</span>
                    @if ($rating->comment)
                        <p class="cell-muted">{{ $rating->comment }}</p>
                    @endif
                </div>
                <span class="rating-stars">{{ str_repeat('★', $rating->rating) }}{{ str_repeat('☆', 5 - $rating->rating) }}</span>
            </div>
        @empty
            <p class="empty-note">No reviews yet.</p>
        @endforelse

    </div>


    <!-- RECENT NOTIFICATIONS -->
    <div class="detail-section">

        <h2 class="section-title">Recent notifications</h2>

        @forelse ($user->notifications as $notification)
            <div class="mini-row">
                <div>
                    <strong>{{ $notification->title }}</strong>
                    <p class="cell-muted">{{ $notification->message }}</p>
                </div>
                <span class="cell-muted">
                    {{ $notification->read_at ? 'Read' : 'Unread' }}
                </span>
            </div>
        @empty
            <p class="empty-note">No notifications sent to this user.</p>
        @endforelse

    </div>


    <!-- DANGER ZONE -->
    @if ($user->id !== auth()->id())
        <div class="detail-section danger-zone">

            <h2 class="section-title">Danger zone</h2>

            <form method="POST" action="{{ route('admin.users.delete', $user) }}"
                  onsubmit="return confirm('Permanently delete {{ $user->name }}? This also removes their listings and exchanges.');">
                @csrf @method('DELETE')
                <button type="submit" class="btn-primary btn-danger">Delete this user</button>
            </form>

        </div>
    @endif

</section>

@endsection
