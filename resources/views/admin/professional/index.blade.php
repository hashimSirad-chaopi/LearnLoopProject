<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - Professional Verification</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div class="admin-container">

    @include('partials.admin-sidebar')

    <main class="main-content" data-live>
        <header class="top-header">
            <div class="header-left">
                <span class="header-label">ADMIN PANEL</span>
                <h1>Professional Verification</h1>
                <p>Members who say they already work with a skill send proof and pass a hard quiz in the app. Check the proof, then approve or decline. Everyone else earns their teaching level by passing lessons.</p>
            </div>
        </header>

        <section class="users-page">
            <div class="ll-scroll">
                <div class="ll-page">
                    @include('partials.flash')

                    <div class="ll-tabs">
                        @foreach(['in_review' => 'Waiting for you', 'approved' => 'Approved', 'rejected' => 'Declined', 'quiz' => 'Taking the quiz', 'failed' => 'Failed the quiz'] as $key => $label)
                            <a class="ll-tab {{ $status === $key ? 'active' : '' }}" href="{{ route('admin.professional', ['status' => $key]) }}">{{ $label }} ({{ (int) ($counts[$key] ?? 0) }})</a>
                        @endforeach
                    </div>

                    @forelse($requests as $row)
                        <div class="ll-card">
                            <div class="ll-row between">
                                <div>
                                    <h3>{{ $row->user?->name ?? 'Deleted user' }} <span class="ll-muted">· {{ $row->skill?->icon }} {{ $row->skill?->title }}</span></h3>
                                    <p class="ll-muted" style="margin:0;">
                                        {{ $row->user?->email }} · sent {{ $row->updated_at->diffForHumans() }}
                                        @if($row->quiz_total) · hard quiz {{ $row->quiz_score }}/{{ $row->quiz_total }} (pass mark {{ \App\Models\ProfessionalVerification::passMark($row->quiz_total) }}) @endif
                                        @if($row->attempts > 1) · {{ $row->attempts }} attempts @endif
                                    </p>
                                </div>
                                <span class="ll-badge {{ $row->status === 'approved' ? 'published' : ($row->status === 'in_review' ? 'pending' : ($row->status === 'rejected' ? 'rejected' : 'pending')) }}">
                                    {{ ['in_review' => 'Waiting', 'approved' => 'Approved', 'rejected' => 'Declined', 'quiz' => 'Quiz not taken', 'failed' => 'Quiz failed'][$row->status] ?? $row->status }}
                                </span>
                            </div>

                            @if($row->proof_note)
                                <p style="margin-top:10px;"><strong>Member's note:</strong> {{ $row->proof_note }}</p>
                            @endif

                            @if($row->proof_path)
                                <div style="margin-top:10px;">
                                    <strong>Proof:</strong>
                                    <a href="{{ $row->proof_url }}" target="_blank" rel="noopener">{{ $row->proof_name ?: 'Open file' }}</a>
                                    @if($row->proofIsImage())
                                        <div style="margin-top:8px;"><a href="{{ $row->proof_url }}" target="_blank" rel="noopener"><img src="{{ $row->proof_url }}" alt="Proof" style="max-width:360px;max-height:260px;border-radius:10px;border:1px solid var(--ll-border);"></a></div>
                                    @endif
                                </div>
                            @endif

                            @if($row->status === 'rejected' && $row->rejection_reason)
                                <div class="ll-note danger" style="margin-top:10px;"><strong>Declined:</strong> {{ $row->rejection_reason }}</div>
                            @endif
                            @if($row->reviewer)
                                <p class="ll-muted" style="margin-top:8px;">Reviewed by {{ $row->reviewer->name }} {{ $row->reviewed_at?->diffForHumans() }}</p>
                            @endif

                            @if(in_array($row->status, ['in_review', 'approved'], true))
                                <div class="ll-row" style="margin-top:12px; align-items:flex-start;">
                                    @if($row->status === 'in_review')
                                        <form method="POST" action="{{ route('admin.professional.approve', $row) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="disable-btn">Approve as Professional</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.professional.reject', $row) }}" class="ll-form" style="flex:1; min-width:260px;">
                                        @csrf @method('PATCH')
                                        <div>
                                            <label for="reason-{{ $row->id }}">{{ $row->status === 'approved' ? 'Take the Professional level away (the member sees this)' : 'Decline (the member sees this and can send new proof)' }}</label>
                                            <textarea id="reason-{{ $row->id }}" name="rejection_reason" rows="2" required placeholder="e.g. The certificate is unreadable. Please send a clearer photo."></textarea>
                                        </div>
                                        <div><button type="submit" class="disable-btn">{{ $row->status === 'approved' ? 'Remove level' : 'Decline' }}</button></div>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="ll-card"><p class="ll-muted">Nothing here.{{ $status === 'in_review' ? ' No proof is waiting for review.' : '' }}</p></div>
                    @endforelse
                </div>
            </div>
        </section>
    </main>
</div>

</body>
</html>
