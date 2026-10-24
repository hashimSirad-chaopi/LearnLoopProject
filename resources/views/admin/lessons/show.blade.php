<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - Review Lesson</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .lr-avatar { border-radius: 11px; }
        .lr-avatar-lg { border-radius: 14px; }
    </style>
</head>
<body>

<div class="admin-container">

    @include('partials.admin-sidebar')

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <span class="header-label">ADMIN PANEL</span>
                <h1>{{ $lesson->title }}</h1>
                <p>{{ $skill->icon }} {{ $skill->title }} · {{ ucfirst($lesson->skillLevel->name) }} level</p>
            </div>
        </header>

        <section class="users-page">
            <div class="ll-scroll">
                <div class="ll-page">
                    @include('partials.flash')

                    <div class="ll-row between">
                        <a href="{{ $back }}" class="table-btn ll-link">← Back</a>
                        <span class="ll-badge {{ $lesson->status }}">{{ $lesson->status === 'rejected' ? 'Needs changes' : ucfirst($lesson->status) }}</span>
                    </div>

                    <div class="ll-card">
                        <h3>Author</h3>
                        @if($lesson->author)
                            <a href="{{ route('admin.lessons.member', $lesson->author) }}" class="lr-member" style="margin-top:8px;">
                                @include('partials.avatar', ['user' => $lesson->author, 'class' => 'lr-avatar'])
                                <span class="lr-member-text"><strong>{{ $lesson->author->name }}</strong><small>{{ $lesson->author->email }}</small></span>
                            </a>
                        @endif
                        <div class="ll-stat" style="margin-top:8px;">
                            <div><span>Name</span><strong>{{ $lesson->author?->name ?? 'Deleted user' }}</strong></div>
                            <div><span>Approved in {{ $skill->title }}</span><strong>{{ $trust['approved'] }} / {{ \App\Support\AuthorTrust::APPROVALS_NEEDED }}</strong></div>
                            <div><span>Ratings</span><strong>{{ $trust['rating_count'] }}{{ $trust['rating_count'] ? ' · ' . $trust['average'] . '★' : '' }}</strong></div>
                            <div><span>Trusted</span><strong>{{ $trust['is_trusted'] ? 'Yes' : 'Not yet' }}</strong></div>
                        </div>
                        <p class="ll-muted" style="margin-top:10px;">
                            An author becomes trusted in a skill after {{ \App\Support\AuthorTrust::APPROVALS_NEEDED }} lessons you approved by hand and
                            {{ \App\Support\AuthorTrust::RATINGS_NEEDED }}+ learner ratings averaging {{ number_format(\App\Support\AuthorTrust::MIN_RATING, 1) }} or better.
                            Trusted authors publish without waiting for you.
                        </p>
                    </div>

                    <div class="ll-card">
                        <h3>Lesson text</h3>
                        @if($lesson->attachment_path)
                            <p><a href="{{ $lesson->attachment_url }}" target="_blank" rel="noopener">{{ ucfirst($lesson->content_type) }}: {{ $lesson->attachment_name ?: 'open' }}</a></p>
                        @endif
                        <p class="ll-lesson-text">{{ $lesson->content }}</p>
                    </div>

                    <div class="ll-card">
                        <h3>Quiz ({{ $lesson->questions->count() }} {{ \Illuminate\Support\Str::plural('question', $lesson->questions->count()) }})</h3>
                        @foreach($lesson->questions as $question)
                            <div class="ll-question">
                                @php $qm = $question->mediaPayload(); @endphp
                                @if($qm['media_url'])
                                    <div style="margin-bottom:6px;">
                                        @if($qm['media_type'] === 'photo') <img src="{{ $qm['media_url'] }}" alt="" style="max-height:140px;border-radius:8px;">
                                        @elseif($qm['media_type'] === 'video') <video src="{{ $qm['media_url'] }}" controls style="max-height:160px;border-radius:8px;"></video>
                                        @else <audio src="{{ $qm['media_url'] }}" controls></audio>
                                        @endif
                                    </div>
                                @endif
                                <strong>{{ $loop->iteration }}. {{ $question->question_text }}</strong>
                                @foreach($question->options as $option)
                                    <div class="ll-option {{ $option->is_correct ? 'correct' : '' }}">
                                        <span class="mark">{{ $option->is_correct ? '✓' : '•' }}</span>
                                        <span>{{ $option->option_text }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                        <p class="ll-muted">The admin cannot be an expert in every skill. Check for safety, format and obvious mistakes: every question has four options and one correct answer.</p>
                    </div>

                    @if($ratingAverage !== null)
                        <div class="ll-card">
                            <h3>Learner rating</h3>
                            <p>{{ $ratingAverage }}★ from {{ $lesson->ratings->count() }} {{ \Illuminate\Support\Str::plural('learner', $lesson->ratings->count()) }}</p>
                        </div>
                    @endif

                    @if($lesson->status === 'rejected' && $lesson->rejection_reason)
                        <div class="ll-note danger"><strong>Changes requested:</strong> {{ $lesson->rejection_reason }}</div>
                    @endif

                    @if($lesson->status === 'published')
                        <div class="ll-note">
                            @if($lesson->approver)
                                Approved by {{ $lesson->approver->name }}{{ $lesson->reviewed_at ? ' ' . $lesson->reviewed_at->diffForHumans() : '' }}. Counts toward the author's trust.
                            @else
                                Published automatically because the author is trusted in this skill. It does not count toward trust.
                            @endif
                        </div>
                    @endif

                    <div class="ll-card">
                        <h3>Decision</h3>
                        <div class="ll-actions" style="margin-bottom:14px;">
                            @if($lesson->status !== 'published' || ! $lesson->approver)
                                <form method="POST" action="{{ route('admin.lessons.approve', $lesson) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="back" value="{{ $back }}">
                                    <button type="submit" class="settings-save-btn">{{ $lesson->status === 'published' ? 'Mark as approved by me' : 'Approve and publish' }}</button>
                                </form>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('admin.lessons.reject', $lesson) }}" class="ll-form">
                            @csrf @method('PATCH')
                                    <input type="hidden" name="back" value="{{ $back }}">
                            <div>
                                <label for="rejection_reason">Ask for changes (the author sees this in the app)</label>
                                <textarea id="rejection_reason" name="rejection_reason" rows="3" placeholder="Say what to fix, then they can resubmit.">{{ old('rejection_reason', $lesson->rejection_reason) }}</textarea>
                            </div>
                            <div><button type="submit" class="disable-btn">Send back for changes</button></div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

</body>
</html>
