@extends('layouts.admin')

@section('title', 'Exchange #' . $exchange->id)
@section('page-title', 'Exchange #' . $exchange->id)
@section('page-subtitle', 'Review participants, sessions, messages and ratings.')

@section('content')

<section class="admin-page">

    <a href="{{ route('admin.exchanges.index') }}" class="back-link">&larr; Back to all exchanges</a>


    <!-- SUMMARY -->
    <div class="detail-grid">

        <div class="detail-card">
            <span class="card-label">PROVIDER</span>
            @if ($exchange->provider)
                <h3>
                    <a href="{{ route('admin.users.show', $exchange->provider) }}" class="cell-link">
                        {{ $exchange->provider->name }}
                    </a>
                </h3>
                <p class="card-description">{{ $exchange->provider->email }}</p>
            @else
                <h3>Deleted user</h3>
            @endif
            <p class="card-description">Teaches: <strong>{{ $exchange->skill_offered }}</strong></p>
        </div>

        <div class="detail-card">
            <span class="card-label">LEARNER</span>
            @if ($exchange->learner)
                <h3>
                    <a href="{{ route('admin.users.show', $exchange->learner) }}" class="cell-link">
                        {{ $exchange->learner->name }}
                    </a>
                </h3>
                <p class="card-description">{{ $exchange->learner->email }}</p>
            @else
                <h3>Deleted user</h3>
            @endif
            <p class="card-description">Wants: <strong>{{ $exchange->skill_wanted }}</strong></p>
        </div>

        <div class="detail-card">
            <span class="card-label">STATUS</span>
            <h3>
                <span class="status-badge status-{{ $exchange->status }}">
                    {{ ucfirst($exchange->status) }}
                </span>
            </h3>
            <p class="card-description">Opened {{ $exchange->created_at->diffForHumans() }}</p>
            @if ($exchange->resolution)
                <p class="card-description">
                    Resolution: <strong>{{ str_replace('_', ' ', $exchange->resolution) }}</strong>
                </p>
            @endif
        </div>

    </div>


    @if ($exchange->admin_notes)
        <div class="admin-note-box">
            <span class="card-label">ADMIN NOTES</span>
            <p>{{ $exchange->admin_notes }}</p>
        </div>
    @endif


    <!-- ADMIN ACTIONS -->
    <div class="detail-section">

        <h2 class="section-title">Admin Actions</h2>

        <div class="action-columns">

            <!-- Change status -->
            <form method="POST" action="{{ route('admin.exchanges.updateStatus', $exchange) }}" class="action-form">
                @csrf @method('PATCH')

                <h3>Change status</h3>

                <label for="status">New status</label>
                <select name="status" id="status">
                    @foreach ($statusOptions as $option)
                        <option value="{{ $option }}" {{ $exchange->status === $option ? 'selected' : '' }}>
                            {{ ucfirst($option) }}
                        </option>
                    @endforeach
                </select>

                <label for="admin_notes">Notes (optional)</label>
                <textarea name="admin_notes" id="admin_notes" rows="3"
                          placeholder="Why is the status changing?">{{ old('admin_notes') }}</textarea>

                <button type="submit" class="btn-primary">Update status</button>
            </form>


            <!-- Resolve dispute -->
            <form method="POST" action="{{ route('admin.exchanges.resolve', $exchange) }}" class="action-form">
                @csrf @method('PATCH')

                <h3>Resolve dispute</h3>

                <label for="resolution">Outcome</label>
                <select name="resolution" id="resolution">
                    <option value="provider_favoured">Provider favoured</option>
                    <option value="learner_favoured">Learner favoured</option>
                    <option value="mutual_cancellation">Mutual cancellation</option>
                </select>

                <label for="notes">Reason (required)</label>
                <textarea name="notes" id="notes" rows="3"
                          placeholder="Explain the decision for the record">{{ old('notes') }}</textarea>

                <button type="submit" class="btn-primary">Record resolution</button>
            </form>

        </div>

    </div>


    <!-- SESSIONS -->
    <div class="detail-section">

        <h2 class="section-title">Sessions ({{ $exchange->schedules->count() }})</h2>

        @forelse ($exchange->schedules as $schedule)
            <div class="mini-row">
                <div>
                    <strong>{{ \Carbon\Carbon::parse($schedule->date)->format('D, j M Y') }}</strong>
                    <span class="cell-muted">
                        {{ \Carbon\Carbon::parse($schedule->time_start)->format('H:i') }}
                        &ndash;
                        {{ \Carbon\Carbon::parse($schedule->time_end)->format('H:i') }}
                    </span>
                    @if ($schedule->location)
                        <span class="cell-muted">&middot; {{ $schedule->location }}</span>
                    @endif
                </div>
                <span class="status-badge status-{{ $schedule->status }}">
                    {{ ucfirst($schedule->status) }}
                </span>
            </div>
        @empty
            <p class="empty-note">No sessions scheduled for this exchange.</p>
        @endforelse

    </div>


    <!-- MESSAGES -->
    <div class="detail-section">

        <h2 class="section-title">Messages ({{ $exchange->messages->count() }})</h2>

        @forelse ($exchange->messages as $message)
            <div class="message-row">
                <div class="message-meta">
                    <strong>{{ $message->sender->name ?? 'Deleted user' }}</strong>
                    <span class="cell-muted">&rarr; {{ $message->recipient->name ?? 'Deleted user' }}</span>
                    <span class="cell-muted">{{ $message->created_at->diffForHumans() }}</span>
                </div>
                <p>{{ $message->message_text }}</p>
            </div>
        @empty
            <p class="empty-note">No messages exchanged yet.</p>
        @endforelse

    </div>


    <!-- RATINGS -->
    <div class="detail-section">

        <h2 class="section-title">Ratings ({{ $exchange->ratings->count() }})</h2>

        @forelse ($exchange->ratings as $rating)
            <div class="mini-row">
                <div>
                    <strong>{{ $rating->rater->name ?? 'Deleted user' }}</strong>
                    <span class="cell-muted">rated {{ $rating->ratedUser->name ?? 'Deleted user' }}</span>
                    @if ($rating->comment)
                        <p class="cell-muted">{{ $rating->comment }}</p>
                    @endif
                    @include('admin.partials.rating-proof', ['rating' => $rating])
                </div>
                <span class="rating-stars">{{ str_repeat('★', $rating->rating) }}{{ str_repeat('☆', 5 - $rating->rating) }}</span>
            </div>
        @empty
            <p class="empty-note">No ratings submitted for this exchange.</p>
        @endforelse

    </div>

</section>

@endsection
