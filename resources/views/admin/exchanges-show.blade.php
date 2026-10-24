<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - Exchange Details</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div class="admin-container">

    @include('partials.admin-sidebar')

    <main class="main-content" data-live>
        <header class="top-header">
            <div class="header-left">
                <span class="header-label">ADMIN PANEL</span>
                <h1>Exchange #EX-{{ str_pad($exchange->id, 3, '0', STR_PAD_LEFT) }}</h1>
                <p>Review and manage this exchange.</p>
            </div>
        </header>

        <section class="users-page">
            <div class="ll-scroll">
                <div class="ll-page">
                    @include('partials.flash')

                    <div class="ll-row between">
                        <a href="{{ route('admin.exchanges') }}" class="table-btn ll-link">← All exchanges</a>
                        <div class="ll-actions">
                            @if($exchange->isLearnerOnly())<span class="ll-badge learner-only">Learner only</span>@endif
                            <span class="ll-badge {{ $exchange->status }}">{{ ucfirst($exchange->status) }}</span>
                        </div>
                    </div>

                    @if($exchange->status === 'disputed')
                        <div class="ll-note danger">
                            <strong>Dispute{{ $exchange->disputedBy ? ' filed by ' . $exchange->disputedBy->name : '' }}{{ $exchange->disputed_at ? ' ' . $exchange->disputed_at->diffForHumans() : '' }}:</strong>
                            {{ $exchange->dispute_reason ?: 'No reason was recorded.' }}
                            <br>Read the evidence below, then set the outcome under “Decision”. Both members are notified in the app.
                        </div>
                    @elseif($exchange->resolved_at)
                        <div class="ll-note">Dispute resolved {{ $exchange->resolved_at->diffForHumans() }}.</div>
                    @endif

                    @if($exchange->cancel_reason)
                        <div class="ll-note">
                            <strong>Cancel request by {{ $exchange->cancelRequestedBy->name ?? 'a member' }}{{ $exchange->cancel_requested_at ? ' ' . $exchange->cancel_requested_at->diffForHumans() : '' }}:</strong>
                            {{ $exchange->cancel_reason }}
                            <br>
                            @if($exchange->status === 'cancelled')
                                The other member accepted, so the exchange was cancelled by agreement.
                            @elseif($exchange->cancel_declined_at)
                                The other member declined{{ $exchange->cancel_decline_note ? ': ' . $exchange->cancel_decline_note : '.' }}
                            @else
                                Waiting for the other member to accept or decline.
                            @endif
                        </div>
                    @endif

                    <div class="ll-card">
                        <h3>Who and what</h3>
                        <div class="ll-stat" style="margin-top:8px;">
                            <div><span>Provider (teaches)</span><strong>{{ $exchange->provider->name ?? 'N/A' }}</strong></div>
                            <div><span>Learner</span><strong>{{ $exchange->learner->name ?? 'N/A' }}</strong></div>
                            <div><span>Provider teaches</span><strong>{{ $exchange->skill_offered ?: '—' }}</strong></div>
                            <div><span>Learner teaches back</span><strong>{{ $exchange->skill_wanted ?: 'Nothing (learner only)' }}</strong></div>
                        </div>
                    </div>

                    @if($exchange->exchangeLessons->isNotEmpty())
                        <div class="ll-card">
                            <h3>Lessons written inside this exchange</h3>
                            <p class="ll-muted">Private lessons a coach wrote for their partner. They are not reviewed, so read them here if there is a complaint.</p>
                            @foreach($exchange->exchangeLessons as $extra)
                                <div class="ll-question">
                                    <strong>{{ $extra->title }}</strong>
                                    <div class="ll-muted">by {{ $extra->author?->name ?? 'Deleted user' }} · {{ $extra->created_at->diffForHumans() }}</div>
                                    <p class="ll-lesson-text" style="margin-top:8px;">{{ $extra->content }}</p>
                                    @foreach(($extra->questions ?? []) as $q)
                                        <div style="margin-top:8px;">
                                            <strong>{{ $loop->iteration }}. {{ $q['text'] ?? '' }}</strong>
                                            @foreach(($q['options'] ?? []) as $oi => $text)
                                                <div class="ll-option {{ (int) ($q['correct'] ?? -1) === $oi ? 'correct' : '' }}">
                                                    <span class="mark">{{ (int) ($q['correct'] ?? -1) === $oi ? '✓' : '•' }}</span><span>{{ $text }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="ll-card">
                        <h3>Sessions</h3>
                        @forelse($exchange->schedules as $schedule)
                            <div class="ll-lesson">
                                <div>
                                    <strong>{{ \Illuminate\Support\Carbon::parse($schedule->date)->format('M j, Y') }} · {{ $schedule->time_start }}–{{ $schedule->time_end }}</strong>
                                    <div class="ll-muted">{{ $schedule->location ?: 'No location' }}{{ $schedule->notes ? ' · ' . $schedule->notes : '' }}</div>
                                </div>
                                <span class="ll-badge {{ $schedule->status === 'confirmed' ? 'ongoing' : $schedule->status }}">{{ ucfirst($schedule->status) }}</span>
                            </div>
                        @empty
                            <p class="ll-muted">No sessions scheduled.</p>
                        @endforelse
                    </div>

                    <div class="ll-card">
                        <h3>Ratings</h3>
                        @forelse($exchange->ratings as $rating)
                            <p><strong>{{ $rating->rater?->name ?? 'Deleted user' }}</strong> gave {{ $rating->rating }}★{{ $rating->comment ? ' — “' . $rating->comment . '”' : '' }}</p>
                            @include('admin.partials.rating-proof', ['rating' => $rating])
                        @empty
                            <p class="ll-muted">Nobody has rated this exchange yet.</p>
                        @endforelse
                    </div>

                    <div class="ll-card">
                        <h3>Messages in this exchange</h3>
                        @forelse($exchange->messages->sortBy('created_at') as $message)
                            <p><strong>{{ $message->sender?->name ?? 'Deleted user' }}</strong> <span class="ll-muted">{{ $message->created_at->format('M j, g:i A') }}</span><br>{{ $message->message_text }}</p>
                        @empty
                            <p class="ll-muted">No messages are linked to this exchange.</p>
                        @endforelse
                    </div>

                    <div class="ll-card">
                        <h3>Decision</h3>
                        <form class="ll-form" method="POST" action="{{ route('admin.exchanges.update', $exchange->id) }}">
                            @csrf
                            @method('PATCH')
                            <div>
                                <label for="status">Status</label>
                                <select id="status" name="status">
                                    @foreach(['pending' => 'Pending', 'ongoing' => 'Ongoing', 'completed' => 'Completed', 'disputed' => 'Disputed', 'cancelled' => 'Cancelled'] as $value => $label)
                                        <option value="{{ $value }}" {{ old('status', $exchange->status) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="admin_notes">Admin note (members see this when you resolve a dispute)</label>
                                <textarea id="admin_notes" name="admin_notes" rows="3">{{ old('admin_notes', $exchange->admin_notes) }}</textarea>
                            </div>
                            <div class="settings-actions">
                                <button type="submit" class="settings-save-btn">Update Exchange</button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('admin.exchanges.delete', $exchange->id) }}" onsubmit="return confirm('Delete this exchange?');" style="margin-top:15px;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="delete-btn">Delete Exchange</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

</body>
</html>
