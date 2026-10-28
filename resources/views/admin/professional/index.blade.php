<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - Professional Verification</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
/* ---------- PROFESSIONAL VERIFICATION ---------- */
.pv-card { display: flex; flex-direction: column; gap: 14px; padding: 20px 22px; }
.pv-head { display: flex; align-items: center; gap: 12px; }
.pv-avatar {
    display: grid; place-items: center; flex-shrink: 0; width: 44px; height: 44px; border-radius: 12px; position: relative; overflow: hidden;
    background: var(--ll-forest); color: var(--ll-mint); font-weight: 900; font-size: 16px;
}
.pv-avatar .avatar-img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; border-radius: inherit; }
.pv-who { flex: 1; min-width: 0; }
.pv-who h3 { margin: 0; color: var(--ll-forest); font-size: 16px; font-weight: 900; }
.pv-who p { margin: 2px 0 0; color: var(--ll-muted); font-size: 12.5px; overflow-wrap: anywhere; }

.pv-chips { display: flex; flex-wrap: wrap; gap: 8px; }
.pv-chip {
    display: inline-flex; align-items: center; gap: 6px; padding: 5px 11px; border-radius: 999px;
    border: 1px solid var(--ll-border); background: var(--ll-surface-2); color: var(--ll-forest);
    font-size: 12px; font-weight: 800;
}
.pv-chip.good { background: var(--ll-mint-soft); border-color: #CFE6B8; color: #35502A; }
.pv-chip.bad { background: var(--ll-danger-soft); border-color: #E8C4C1; color: #8A3B37; }

.pv-body { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(260px, 1fr); gap: 18px; align-items: start; }
.pv-body.single { grid-template-columns: minmax(0, 1fr); }
.pv-label { margin-bottom: 8px; color: var(--ll-muted); font-size: 11px; font-weight: 800; letter-spacing: .5px; text-transform: uppercase; }

.pv-evidence { display: flex; flex-direction: column; min-width: 0; }
.pv-note {
    margin: 0 0 16px; padding: 10px 14px; border-left: 4px solid var(--ll-mint-dark, var(--ll-forest));
    border-radius: 0 10px 10px 0; background: var(--ll-surface-2); color: var(--ll-text); font-size: 14px; overflow-wrap: anywhere;
}
.pv-proof {
    display: block; width: fit-content; max-width: min(100%, 320px); overflow: hidden; border: 1px solid var(--ll-border);
    border-radius: 12px; background: #111; transition: box-shadow .15s ease, transform .15s ease;
}
.pv-proof:hover { box-shadow: var(--ll-shadow); transform: translateY(-1px); }
.pv-proof img { display: block; max-width: 100%; max-height: 260px; object-fit: contain; }
.pv-file {
    display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin-top: 10px; padding: 9px 12px;
    border: 1px solid var(--ll-border); border-radius: 10px; background: var(--ll-surface); color: var(--ll-forest);
    font-size: 13px; font-weight: 700; text-decoration: none;
}
.pv-file span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pv-file small { flex-shrink: 0; color: var(--ll-muted); font-size: 11.5px; font-weight: 700; }
.pv-file:hover { border-color: var(--ll-forest); background: var(--ll-surface-2); }
.pv-empty { margin: 0; color: var(--ll-muted); font-size: 13px; }

.pv-decision {
    display: flex; flex-direction: column; padding: 16px; border: 1px solid var(--ll-border);
    border-radius: 14px; background: var(--ll-surface-2);
}
.pv-btn {
    display: inline-flex; align-items: center; justify-content: center; width: 100%; padding: 11px 16px;
    border-radius: 10px; font: inherit; font-size: 14px; font-weight: 800; cursor: pointer;
    transition: background .15s ease, color .15s ease, border-color .15s ease, transform .15s ease;
}
.pv-btn:hover { transform: translateY(-1px); }
.pv-btn:focus-visible { outline: 2px solid var(--ll-forest); outline-offset: 2px; }
.pv-btn.approve { border: 1px solid var(--ll-forest); background: var(--ll-forest); color: var(--ll-mint); }
.pv-btn.approve:hover { background: var(--ll-forest-2, var(--ll-forest)); }
.pv-btn.decline { border: 1px solid var(--ll-danger); background: transparent; color: var(--ll-danger); }
.pv-btn.decline:hover { background: var(--ll-danger); color: #fff; }

.pv-or { display: flex; align-items: center; gap: 10px; margin: 14px 0; color: var(--ll-muted); font-size: 11px; font-weight: 800; text-transform: uppercase; }
.pv-or::before, .pv-or::after { content: ""; flex: 1; height: 1px; background: var(--ll-border); }

.pv-reject { display: flex; flex-direction: column; gap: 8px; }
.pv-reject label { color: var(--ll-forest); font-size: 13px; font-weight: 800; }
.pv-reject textarea {
    width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1px solid var(--ll-border-dark); border-radius: 10px;
    background: var(--ll-surface); color: var(--ll-text); font: inherit; font-size: 13.5px; resize: vertical; min-height: 78px;
}
.pv-reject textarea:focus { outline: 2px solid var(--ll-forest); outline-offset: 1px; }
.pv-reject small { color: var(--ll-muted); font-size: 11.5px; }

.pv-foot { margin: 0; }
.pv-reviewed { margin: 0; color: var(--ll-muted); font-size: 12px; }

@media (max-width: 820px) {
    .pv-body { grid-template-columns: minmax(0, 1fr); }
    .pv-head { flex-wrap: wrap; }
}
</style>
</head>
<body>

<div class="admin-container">

    @include('partials.admin-sidebar')

    <main class="main-content" data-live>
        <header class="top-header">
            <div class="header-left">
                <span class="header-label">ADMIN PANEL</span>
                <h1>Professional Verification</h1>
                <p>Users who say they already work with a skill send proof and pass a hard quiz in the app. Check the proof, then approve or decline. Everyone else earns their teaching level by passing lessons.</p>
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
                        @php
                            $passMark = $row->quiz_total ? \App\Models\ProfessionalVerification::passMark($row->quiz_total) : null;
                            $quizPassed = $row->quiz_total && $row->quiz_score >= $passMark;
                            $statusLabel = ['in_review' => 'Waiting', 'approved' => 'Approved', 'rejected' => 'Declined', 'quiz' => 'Quiz not taken', 'failed' => 'Quiz failed'][$row->status] ?? $row->status;
                            $badgeClass = $row->status === 'approved' ? 'published' : ($row->status === 'rejected' ? 'rejected' : 'pending');
                            $canReview = in_array($row->status, ['in_review', 'approved'], true);
                        @endphp
                        <article class="ll-card pv-card">
                            <header class="pv-head">
                                @if($row->user)
                                    @include('partials.avatar', ['user' => $row->user, 'class' => 'pv-avatar'])
                                @else
                                    <span class="pv-avatar has-avatar"><span class="avatar-initials">?</span></span>
                                @endif
                                <div class="pv-who">
                                    <h3>{{ $row->user?->name ?? 'Deleted user' }}</h3>
                                    <p>{{ $row->user?->email }} · sent {{ $row->updated_at->diffForHumans() }}</p>
                                </div>
                                <span class="ll-badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                            </header>

                            <div class="pv-chips">
                                <span class="pv-chip">{{ $row->skill?->icon }} {{ $row->skill?->title }}</span>
                                @if($row->quiz_total)
                                    <span class="pv-chip {{ $quizPassed ? 'good' : 'bad' }}">Hard quiz {{ $row->quiz_score }}/{{ $row->quiz_total }} · pass mark {{ $passMark }}</span>
                                @endif
                                @if($row->attempts > 1)
                                    <span class="pv-chip">{{ $row->attempts }} attempts</span>
                                @endif
                            </div>

                            <div class="pv-body {{ $canReview ? '' : 'single' }}">
                                <section class="pv-evidence">
                                    @if($row->proof_note)
                                        <div class="pv-label">User's note</div>
                                        <blockquote class="pv-note">{{ $row->proof_note }}</blockquote>
                                    @endif

                                    <div class="pv-label">Proof</div>
                                    @if($row->proof_path)
                                        @if($row->proofIsImage())
                                            <a class="pv-proof" href="{{ $row->proof_url }}" target="_blank" rel="noopener" title="Open full size" style="display:block;width:fit-content;max-width:min(100%,320px);">
                                                <img src="{{ $row->proof_url }}" alt="Proof sent by {{ $row->user?->name }}" loading="lazy" style="display:block;width:auto;height:auto;max-width:100%;max-height:260px;object-fit:contain;">
                                            </a>
                                        @endif
                                        <a class="pv-file" href="{{ $row->proof_url }}" target="_blank" rel="noopener">
                                            <span>{{ $row->proof_name ?: 'Open file' }}</span>
                                            <small>Open full size ↗</small>
                                        </a>
                                    @else
                                        <p class="pv-empty">No proof has been uploaded yet.</p>
                                    @endif
                                </section>

                                @if($canReview)
                                    <aside class="pv-decision">
                                        @if($row->status === 'in_review')
                                            <div class="pv-label">Your decision</div>
                                            <form method="POST" action="{{ route('admin.professional.approve', $row) }}">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="pv-btn approve">Approve as Professional</button>
                                            </form>
                                            <div class="pv-or"><span>or</span></div>
                                        @endif

                                        <form method="POST" action="{{ route('admin.professional.reject', $row) }}" class="pv-reject">
                                            @csrf @method('PATCH')
                                            <label for="reason-{{ $row->id }}">{{ $row->status === 'approved' ? 'Take the Professional level away' : 'Decline with a reason' }}</label>
                                            <textarea id="reason-{{ $row->id }}" name="rejection_reason" rows="3" required placeholder="e.g. The certificate is unreadable. Please send a clearer photo."></textarea>
                                            <small>{{ $row->status === 'approved' ? 'The user sees this reason.' : 'The user sees this and can send new proof.' }}</small>
                                            <button type="submit" class="pv-btn decline">{{ $row->status === 'approved' ? 'Remove level' : 'Decline' }}</button>
                                        </form>
                                    </aside>
                                @endif
                            </div>

                            @if($row->status === 'rejected' && $row->rejection_reason)
                                <div class="ll-note danger pv-foot"><strong>Declined:</strong> {{ $row->rejection_reason }}</div>
                            @endif
                            @if($row->reviewer)
                                <p class="pv-reviewed">Reviewed by {{ $row->reviewer->name }} {{ $row->reviewed_at?->diffForHumans() }}</p>
                            @endif
                        </article>
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
