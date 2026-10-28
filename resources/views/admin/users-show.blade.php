<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - View User</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .ud-head { padding: 0; overflow: hidden; }
        .ud-banner { height: 120px; background: linear-gradient(120deg, #16231C, #2B3C31); background-size: cover; background-position: center; }
        .ud-id { display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-start; padding: 0 20px; }
        .ud-avatar { position: relative; margin-top: -42px; width: 84px; height: 84px; border-radius: 18px; overflow: hidden; border: 4px solid #FBFDF9; background: #B7FF72; flex-shrink: 0; }
        .ud-avatar img, .ud-initials { position: absolute; inset: 0; width: 100%; height: 100%; }
        .ud-avatar img { object-fit: cover; }
        .ud-initials { display: flex; align-items: center; justify-content: center; font-size: 30px; font-weight: 800; color: #16231C; }
        .ud-id-text { flex: 1; min-width: 200px; padding-top: 14px; }
        .ud-id-text h2 { margin: 0; font-size: 22px; overflow-wrap: anywhere; }
        .ud-id-text p { margin: 2px 0 6px; }
        .ud-badges { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .ud-actions { display: flex; gap: 8px; align-items: center; padding-top: 14px; }
        .ud-head > p, .ud-head > .ud-alert { margin-left: 20px; margin-right: 20px; }
        .ud-head > .ud-bio { margin-top: 16px; }
        .ud-head > .ll-muted { margin-top: 8px; }
        .ud-head > p:last-child { margin-bottom: 16px; }
        .ud-bio { margin-top: 14px; white-space: pre-line; overflow-wrap: anywhere; }
        .ud-alert { margin-top: 14px; padding: 10px 14px; border-radius: 10px; background: #F9E9E7; border: 1px solid #E3B8B4; color: #7A2F2A; font-size: 13px; line-height: 1.5; }
        .ud-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; }
        .ud-stats > div, .ud-tile { background: var(--ll-surface, #FBFDF9); border: 1px solid var(--ll-border, #CAD5CB); border-radius: 12px; padding: 12px 14px; }
        .ud-tile { display: block; text-decoration: none; color: inherit; position: relative; }
        a.ud-tile:hover { border-color: #55783B; box-shadow: 0 2px 10px rgba(22,35,28,.10); }
        .ud-tile em { display: block; margin-top: 6px; font-style: normal; font-size: 12px; font-weight: 800; color: #55783B; }
        .ud-tile.off { opacity: .6; }
        .ud-stats span { display: block; color: var(--ll-muted, #69766E); font-size: 11px; font-weight: 800; letter-spacing: .6px; text-transform: uppercase; }
        .ud-stats strong { display: block; font-size: 22px; color: var(--ll-forest, #16231C); }
        .ud-stats small { color: var(--ll-muted, #69766E); font-size: 12px; }
        .ud-stats .ud-warn, .ud-tile.ud-warn { background: #FFF4DD; border-color: #E4C77A; }
        .ud-cols { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 16px; align-items: start; }
        .ud-main, .ud-side { display: flex; flex-direction: column; gap: 16px; min-width: 0; }
        .lp-skill { padding: 14px 0; border-top: 1px solid var(--ll-border, #CAD5CB); }
        .lp-skill:first-of-type { border-top: 0; }
        .lp-head { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .lp-total { display: flex; align-items: center; gap: 12px; margin-top: 8px; }
        .lp-total .lp-track { flex: 1; }
        .lp-track { height: 8px; border-radius: 999px; background: #EDF3EB; overflow: hidden; }
        .lp-track span { display: block; height: 100%; border-radius: 999px; background: #55783B; }
        .lp-now { margin: 8px 0 10px; font-size: 13px; }
        .lp-levels { display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: 10px; }
        .lp-level .lp-track { height: 6px; }
        .lp-level.done .lp-track span { background: #8FD64C; }
        .lp-level.locked { opacity: .55; }
        .lp-level-text { display: flex; justify-content: space-between; gap: 6px; margin-top: 4px; font-size: 12px; }
        .lp-level.now .lp-level-text strong { color: #16231C; }
        .lp-level-text span { color: var(--ll-muted, #69766E); }
        .ud-item { padding: 10px 0; border-top: 1px solid var(--ll-border, #CAD5CB); }
        .ud-item:first-of-type { border-top: 0; padding-top: 4px; }
        .ud-item p { margin: 4px 0 0; }
        .ud-facts { margin: 8px 0 0; display: grid; gap: 8px; }
        .ud-facts div { display: flex; justify-content: space-between; gap: 12px; font-size: 13px; }
        .ud-facts dt { color: var(--ll-muted, #69766E); flex-shrink: 0; }
        .ud-facts dd { margin: 0; font-weight: 700; text-align: right; overflow-wrap: anywhere; }
        .ud-pills { display: flex; flex-wrap: wrap; gap: 6px; margin: 8px 0 6px; }
        @media (max-width: 1000px) { .ud-cols { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="admin-container">

      @include('partials.admin-sidebar')

    <main class="main-content" data-live>
        @php
            // Go back to the exact users list the admin came from (keeps search, filters and page).
            $prev = url()->previous();
            $backUrl = preg_match('#/admin/users(\?|$)#', (string) parse_url($prev, PHP_URL_PATH).'?'.(string) parse_url($prev, PHP_URL_QUERY))
                && str_starts_with($prev, url('/'))
                ? $prev : route('admin.users');
        @endphp
        <header class="top-header">
            <div class="header-left">
                <span class="header-label">ADMIN PANEL</span>
                <h1>User Details</h1>
                <p>Viewing account information.</p>
            </div>
        </header>

        <section class="users-page">
            <div class="ll-scroll">
                <div class="ll-page">
                    <div><a href="{{ $backUrl }}" class="table-btn" style="text-decoration:none;">&larr; Back to Manage Users</a></div>
                    @include('partials.flash')

                    @php
                        $isSelf = $user->id === auth()->id();
                        $locked = $isSelf || $user->role === 'admin';
                    @endphp

                    {{-- ===== Header ===== --}}
                    <div class="ll-card ud-head">
                        <div class="ud-banner" @if($user->banner_url) style="background-image:url('{{ $user->banner_url }}')" @endif></div>
                        <div class="ud-id">
                            <div class="ud-avatar">
                                <span class="ud-initials">{{ mb_strtoupper(mb_substr(trim($user->name), 0, 1)) }}</span>
                                @if($user->avatar_url)<img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" onerror="this.remove()">@endif
                            </div>
                            <div class="ud-id-text">
                                <h2>{{ $user->name }} @if($isSelf)<span class="ll-muted">(you)</span>@endif</h2>
                                <p class="ll-muted">{{ $user->email }}</p>
                                <div class="ud-badges">
                                    <span class="ll-badge {{ $user->role === 'admin' ? 'trusted' : '' }}">{{ ucfirst($user->role) }}</span>
                                    <span class="ll-badge {{ ['active' => 'active', 'offline' => 'inactive', 'suspended' => 'rejected'][$user->presence] }}">{{ ucfirst($user->presence) }}</span>
                                    @if($user->presence === 'offline')
                                        <span class="ll-muted">{{ $user->last_seen_at ? 'last seen '.$user->last_seen_at->diffForHumans() : 'not seen yet' }}</span>
                                    @elseif($user->presence === 'active')
                                        <span class="ll-muted">online now</span>
                                    @endif
                                </div>
                            </div>
                            <div class="ud-actions">
                                <a href="{{ route('admin.users.edit', $user->id) }}" class="table-btn" style="text-decoration:none;">Edit</a>
                                @unless($locked)
                                    @if($user->status === 'suspended')
                                        <form method="POST" action="{{ route('admin.users.activate', $user->id) }}" onsubmit="return confirm('Reactivate {{ $user->name }}?');">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="settings-save-btn">Reactivate</button>
                                        </form>
                                    @else
                                        <button type="button" class="delete-btn"
                                                data-suspend-open data-suspend-url="{{ route('admin.users.suspend', $user->id) }}" data-suspend-name="{{ $user->name }}">Suspend</button>
                                    @endif
                                @endunless
                            </div>
                        </div>

                        @if($user->status === 'suspended')
                            <div class="ud-alert">
                                <strong>Suspended</strong>
                                {{ $user->suspended_until ? 'until '.$user->suspended_until->format('M j, Y g:i A') : 'until you reactivate the account' }}
                                @if($why = \App\Support\Suspension::describe($user->suspension_reason, $user->suspension_note))
                                    <br>Reason: {{ $why }}
                                @endif
                            </div>
                        @endif

                        @if($user->bio)<p class="ud-bio">{{ $user->bio }}</p>@endif
                        <p class="ll-muted">
                            User since {{ $user->created_at->format('F j, Y') }} ({{ $user->created_at->diffForHumans() }})
                            @if($user->availability) &middot; Availability: {{ $user->availability }} @endif
                        </p>
                    </div>

                    {{-- ===== Numbers ===== --}}
                    <div class="ud-stats">
                        <a class="ud-tile" href="#ratings"><span>Rating</span><strong>{{ $ratingCount ? $ratingAverage . '★' : '—' }}</strong><small>{{ $ratingCount }} {{ \Illuminate\Support\Str::plural('rating', $ratingCount) }}</small><em>Manage &rarr;</em></a>
                        <a class="ud-tile" href="{{ route('admin.exchanges', ['user' => $user->id]) }}"><span>Exchanges</span><strong>{{ $stats['exchanges'] }}</strong><small>{{ $completedExchanges }} completed</small><em>Manage &rarr;</em></a>
                        <a class="ud-tile" href="{{ route('admin.listings', ['user' => $user->id]) }}"><span>Listings</span><strong>{{ $stats['listings'] }}</strong><em>Manage &rarr;</em></a>
                        <a class="ud-tile" href="{{ route('admin.comments', ['user' => $user->id]) }}"><span>Comments</span><strong>{{ $stats['comments'] }}</strong><em>Manage &rarr;</em></a>
                        @if($lessonsWritten > 0)
                            <a class="ud-tile" href="{{ route('admin.lessons.member', $user->id) }}"><span>Lessons written</span><strong>{{ $lessonsWritten }}</strong><em>Review &rarr;</em></a>
                        @else
                            <div class="ud-tile off"><span>Lessons written</span><strong>0</strong></div>
                        @endif
                        <a class="ud-tile {{ $stats['reports_upheld'] > 0 ? 'ud-warn' : '' }}" href="{{ route('admin.reports', ['user' => $user->id]) }}"><span>Reports against</span><strong>{{ $stats['reports_against'] }}</strong>
                            <small>{{ $stats['reports_upheld'] }} upheld &middot; {{ $stats['warnings'] }} {{ \Illuminate\Support\Str::plural('warning', $stats['warnings']) }}</small><em>Manage &rarr;</em>
                        </a>
                    </div>

                    <div class="ud-cols">
                    <div class="ud-main">

                    <div class="ll-card">
                        <h3>Skills they can teach</h3>
                        <p class="ll-muted">Levels are earned, not typed in: a user can teach a skill up to the level whose lessons they passed (Professional needs approved proof + the hard quiz).</p>
                        @forelse($skillsOffered as $row)
                            <div class="ll-lesson">
                                <strong>{{ $row->skill?->icon }} {{ $row->skill?->title ?? 'Removed skill' }}</strong>
                                @if($row->level)<span class="ll-badge {{ $row->level }}">{{ ucfirst($row->level) }}</span>
                                @else<span class="ll-badge pending">Not earned yet</span>@endif
                            </div>
                        @empty
                            <p class="ll-muted">No skills to teach yet. They can still post learner-only listings.</p>
                        @endforelse
                    </div>

                    <div class="ll-card">
                        <h3>Learning progress</h3>
                        <p class="ll-muted">The skills they picked or started, with their progress in each level. A level opens once the one before it is passed.</p>
                        @forelse($learning as $row)
                            @php
                                $wants = in_array($row['skill_id'], $wantedIds);
                                $teaches = in_array($row['skill_id'], $offeredIds);
                                $current = $row['next_level'];
                            @endphp
                            <div class="lp-skill">
                                <div class="lp-head">
                                    <strong>{{ $row['title'] }}</strong>
                                    @if($wants)<span class="ll-badge">Wants to learn</span>@endif
                                    @if($teaches)<span class="ll-badge trusted">Teaches</span>@endif
                                    @if($row['earned_level'])<span class="ll-badge {{ $row['earned_level'] }}">Can teach: {{ ucfirst($row['earned_level']) }}</span>@else<span class="ll-badge pending">No level yet</span>@endif
                                    @if($row['professional'] === 'in_review')<span class="ll-badge pending">Professional proof waiting</span>@endif
                                </div>

                                <div class="lp-total">
                                    <div class="lp-track"><span style="width: {{ $row['percent'] }}%"></span></div>
                                    <span class="ll-muted">{{ $row['lessons_passed'] }}/{{ $row['lessons_total'] }} lessons &middot; {{ $row['percent'] }}%</span>
                                </div>

                                <p class="lp-now">
                                    @if($row['lessons_passed'] === 0 && $current)
                                        Not started yet. First level: <strong>{{ ucfirst($current) }}</strong>
                                    @elseif($current)
                                        Working on <strong>{{ ucfirst($current) }}</strong>
                                    @else
                                        <strong>All levels complete</strong>
                                    @endif
                                </p>

                                <div class="lp-levels">
                                    @foreach(collect($row['levels'])->where('total', '>', 0) as $level)
                                        <div class="lp-level {{ $level['complete'] ? 'done' : ($level['name'] === $current ? 'now' : '') }} {{ $level['unlocked'] || $level['complete'] ? '' : 'locked' }}">
                                            <div class="lp-track"><span style="width: {{ $level['percent'] }}%"></span></div>
                                            <div class="lp-level-text">
                                                <strong>{{ ucfirst($level['name']) }}</strong>
                                                <span>{{ $level['complete'] ? '✓ ' : (($level['unlocked'] || $level['complete']) ? '' : '🔒 ') }}{{ $level['passed'] }}/{{ $level['total'] }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <p class="ll-muted">They have not picked or started a skill with lessons yet.</p>
                        @endforelse
                    </div>

                    <div class="ll-card">
                        <h3>Skills they want to learn</h3>
                        <p style="display:flex;flex-wrap:wrap;gap:8px;">
                            @forelse($skillsWanted as $row)
                                <span class="ll-badge">{{ $row->skill?->icon }} {{ $row->skill?->title ?? 'Removed skill' }}</span>
                            @empty
                                <span class="ll-muted">None listed.</span>
                            @endforelse
                        </p>
                    </div>

                    @if($authorTrust->isNotEmpty())
                        <div class="ll-card">
                            <h3>Lesson author status</h3>
                            <table class="ll-table">
                                <thead><tr><th>Skill</th><th>Approved lessons</th><th>Ratings</th><th>Trusted</th></tr></thead>
                                <tbody>
                                @foreach($authorTrust as $row)
                                    <tr>
                                        <td>{{ $row['skill']->title }}</td>
                                        <td>{{ $row['approved'] }} / {{ \App\Support\AuthorTrust::APPROVALS_NEEDED }}</td>
                                        <td>{{ $row['rating_count'] }}{{ $row['rating_count'] ? ' · ' . $row['average'] . '★' : '' }}</td>
                                        <td><span class="ll-badge {{ $row['is_trusted'] ? 'trusted' : '' }}">{{ $row['is_trusted'] ? 'Trusted' : 'Not yet' }}</span></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <div class="ll-card" id="ratings">
                        <h3>Ratings received</h3>
                        <p class="ll-muted">Newest first, up to 50. Delete a rating if it is abusive or was left by mistake.</p>
                        @forelse($ratings as $rating)
                            <div class="ud-item">
                                <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;">
                                    <strong>{{ str_repeat('★', (int) $rating->rating) }}<span class="ll-muted">{{ str_repeat('★', max(0, 5 - (int) $rating->rating)) }}</span></strong>
                                    <form method="POST" action="{{ route('admin.ratings.delete', $rating->id) }}" onsubmit="return confirm('Delete this rating? This cannot be undone.');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="delete-btn">Delete</button>
                                    </form>
                                </div>
                                @if($rating->comment)<p style="white-space:pre-line;overflow-wrap:anywhere;">{{ $rating->comment }}</p>@endif
                                @include('admin.partials.rating-proof', ['rating' => $rating])
                                <p class="ll-muted">From {{ $rating->rater?->name ?? 'a deleted user' }} &middot; {{ $rating->created_at->diffForHumans() }}
                                    @if($rating->exchange_id) &middot; <a class="cell-link" href="{{ route('admin.exchanges.show', $rating->exchange_id) }}">Exchange #{{ $rating->exchange_id }}</a>@endif</p>
                            </div>
                        @empty
                            <p class="ll-muted">No ratings yet.</p>
                        @endforelse
                    </div>

                    </div>{{-- /ud-main --}}

                    <div class="ud-side">

                        <div class="ll-card">
                            <h3>Account</h3>
                            <dl class="ud-facts">
                                <div><dt>Email</dt><dd>{{ $user->email }}</dd></div>
                                <div><dt>Role</dt><dd>{{ ucfirst($user->role) }}</dd></div>
                                <div><dt>Status</dt><dd>{{ ucfirst($user->presence) }}</dd></div>
                                <div><dt>Member since</dt><dd>{{ $user->created_at->format('M j, Y') }}</dd></div>
                                <div><dt>Last seen</dt><dd>{{ $user->presence === 'active' ? 'Online now' : ($user->last_seen_at ? $user->last_seen_at->diffForHumans() : 'Never') }}</dd></div>
                                <div><dt>Availability</dt><dd>{{ $user->availability ?: 'Not set' }}</dd></div>
                            </dl>
                        </div>

                        <div class="ll-card">
                            <h3>Exchanges</h3>
                            @if($stats['exchanges'] > 0)
                                <div class="ud-pills">
                                    @foreach(['pending' => 'Pending', 'ongoing' => 'Ongoing', 'completed' => 'Completed', 'disputed' => 'Disputed', 'cancelled' => 'Cancelled'] as $key => $label)
                                        @if(($exchangeStatuses[$key] ?? 0) > 0)
                                            <span class="ll-badge {{ ['pending' => 'pending', 'ongoing' => '', 'completed' => 'active', 'disputed' => 'rejected', 'cancelled' => 'inactive'][$key] }}">{{ $label }} {{ $exchangeStatuses[$key] }}</span>
                                        @endif
                                    @endforeach
                                </div>
                                @foreach($recentExchanges as $ex)
                                    @php($partner = $ex->provider_id == $user->id ? $ex->learner : $ex->provider)
                                    <div class="ud-item">
                                        <a class="cell-link" href="{{ route('admin.exchanges.show', $ex->id) }}"><strong>{{ $partner?->name ?? 'Deleted user' }}</strong></a>
                                        <span class="ll-badge">{{ ucfirst($ex->status) }}</span>
                                        <p class="ll-muted">{{ $ex->skillPair() }} &middot; {{ $ex->created_at->diffForHumans() }}</p>
                                    </div>
                                @endforeach
                            @else
                                <p class="ll-muted">No exchanges yet.</p>
                            @endif
                        </div>

                        <div class="ll-card">
                            <h3>Reports against them</h3>
                            @forelse($recentReports as $report)
                                <div class="ud-item">
                                    <strong>{{ \Illuminate\Support\Str::limit($report->title, 60) }}</strong>
                                    <span class="ll-badge {{ ['pending' => 'pending', 'resolved' => 'rejected', 'dismissed' => 'inactive'][$report->status] ?? '' }}">{{ ucfirst($report->status) }}</span>
                                    <p class="ll-muted">By {{ $report->reporter?->name ?? 'a deleted user' }} &middot; {{ $report->created_at->diffForHumans() }}</p>
                                </div>
                            @empty
                                <p class="ll-muted">No one has reported this user.</p>
                            @endforelse
                            @if($stats['reports_against'] > 0)
                                <a class="cell-link" href="{{ route('admin.reports', ['search' => $user->name]) }}">Open in Reports &rarr;</a>
                            @endif
                            @if($stats['reports_filed'] > 0)
                                <p class="ll-muted" style="margin-top:8px;">They have filed {{ $stats['reports_filed'] }} {{ \Illuminate\Support\Str::plural('report', $stats['reports_filed']) }} themselves.</p>
                            @endif
                        </div>

                        <div class="ll-card">
                            <h3>Suspension history</h3>
                            @forelse($history as $entry)
                                <div class="ud-item">
                                    <strong>{{ $entry->action === 'user.suspended' ? 'Suspended' : 'Reactivated' }}</strong>
                                    <span class="ll-muted">{{ $entry->created_at->format('M j, Y g:i A') }}@if($entry->admin) &middot; by {{ $entry->admin->name }}@endif</span>
                                    <p class="ll-muted">{{ \Illuminate\Support\Str::limit($entry->description, 140) }}</p>
                                </div>
                            @empty
                                <p class="ll-muted">Never suspended.</p>
                            @endforelse
                        </div>

                        <div class="ll-card">
                            <h3>Latest comments</h3>
                            @forelse($recentComments as $comment)
                                <div class="ud-item">
                                    <p style="white-space:pre-line;overflow-wrap:anywhere;">{{ \Illuminate\Support\Str::limit($comment->body, 140) }}</p>
                                    <p class="ll-muted">On {{ $comment->listing ? '“'.\Illuminate\Support\Str::limit($comment->listing->title, 40).'”' : 'a deleted listing' }} &middot; {{ $comment->created_at->diffForHumans() }}</p>
                                </div>
                            @empty
                                <p class="ll-muted">No comments yet.</p>
                            @endforelse
                        </div>

                        <div class="ll-card">
                            <h3>Latest listings</h3>
                            @forelse($recentListings as $listing)
                                <div class="ud-item">
                                    <a class="cell-link" href="{{ route('admin.listings.edit', $listing) }}"><strong>{{ \Illuminate\Support\Str::limit($listing->title, 50) }}</strong></a>
                                    <span class="ll-badge {{ $listing->status === 'active' ? 'active' : 'inactive' }}">{{ ucfirst($listing->status) }}</span>
                                    <p class="ll-muted">{{ $listing->created_at->diffForHumans() }}</p>
                                </div>
                            @empty
                                <p class="ll-muted">No listings yet.</p>
                            @endforelse
                        </div>

                    </div>{{-- /ud-side --}}
                    </div>{{-- /ud-cols --}}

                    <div class="settings-actions">
                        <a href="{{ $backUrl }}" class="table-btn">&larr; Back to Manage Users</a>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

@include('partials.suspend-dialog')

</body>
</html>