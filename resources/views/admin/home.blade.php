<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - Admin Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .dash-trend { margin: 2px 0 10px; font-size: 12.5px; color: var(--ll-muted, #69766E); }
        .dash-down { color: #B14D48; font-weight: 800; }
        .dash-susp { font-style: normal; font-size: 10.5px; font-weight: 800; color: #B14D48; background: #F9E9E7; border-radius: 8px; padding: 1px 6px; margin-left: 4px; }
        .activity-indicator.ai-bad { background: #B14D48; box-shadow: 0 0 0 4px rgba(177, 77, 72, .18); }
        .activity-indicator.ai-info { background: #69766E; box-shadow: 0 0 0 4px rgba(105, 118, 110, .16); }
    </style>
</head>
<body>

<div class="admin-container">

    @include('partials.admin-sidebar')

    <main class="main-content" data-live>

        <header class="top-header">
            <div class="header-left">
                <span class="header-label">ADMIN PANEL</span>
                <h1>Dashboard</h1>
                <p>{{ now()->format('l, F j') }} &middot; Welcome back to LearnLoop.</p>
            </div>
        </header>

        <section class="dashboard-content">

            {{-- ===== Date range + export ===== --}}
            <div class="dash-tools">
                <div class="dash-range" aria-label="Date range">
                    @foreach([7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days'] as $d => $label)
                        <a href="{{ route('admin.home', ['range' => $d]) }}" class="{{ $stats['days'] === $d ? 'active' : '' }}">{{ $label }}</a>
                    @endforeach
                </div>
                <a class="dash-export" href="{{ route('admin.dashboard.export', ['range' => $stats['days']]) }}">Export CSV</a>
            </div>

            {{-- ===== Needs your attention (your own notifications) ===== --}}
            @if($attentionTotal > 0)
                <div class="dash-attention">
                    <div class="dash-attention-head">
                        <span class="section-label">NEEDS YOUR ATTENTION</span>
                        <h2>{{ $attentionTotal }} {{ \Illuminate\Support\Str::plural('item', $attentionTotal) }} waiting for you</h2>
                    </div>
                    <div class="dash-chips">
                        @foreach($attention as $item)
                            @if($item['count'] > 0)
                                <a class="dash-chip" href="{{ $item['url'] }}" title="{{ $item['hint'] }}">
                                    <strong>{{ $item['count'] }}</strong>
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            @else
                <div class="dash-attention clear">
                    <div class="dash-attention-head">
                        <span class="section-label">ALL CAUGHT UP</span>
                        <h2>Nothing is waiting for you right now.</h2>
                    </div>
                </div>
            @endif

            {{-- ===== Key numbers ===== --}}
            <div class="dashboard-heading">
                <span class="section-label">OVERVIEW</span>
                <h2>System Overview</h2>
            </div>

            <div class="cards">
                <a class="card" href="{{ route('admin.users') }}">
                    <div class="card-top"><span class="card-label">USERS</span></div>
                    <div class="card-value">{{ number_format($stats['totalMembers']) }}</div>
                    <p class="card-description">
                        @if($stats['newMembersWeek'] > 0)
                            <span class="dash-up">+{{ $stats['newMembersWeek'] }}</span> in the last 7 days
                        @else
                            No new users this week
                        @endif
                    </p>
                </a>

                <a class="card" href="{{ route('admin.listings') }}">
                    <div class="card-top"><span class="card-label">ACTIVE LISTINGS</span></div>
                    <div class="card-value">{{ number_format($stats['activeListings']) }}</div>
                    <p class="card-description">of {{ number_format($stats['totalListings']) }} total listings</p>
                </a>

                <a class="card" href="{{ route('admin.exchanges') }}">
                    <div class="card-top"><span class="card-label">ONGOING EXCHANGES</span></div>
                    <div class="card-value">{{ number_format($stats['exchangeStatuses']['ongoing']) }}</div>
                    <p class="card-description">{{ number_format($stats['exchangeStatuses']['completed']) }} completed so far</p>
                </a>

                <div class="card">
                    <div class="card-top"><span class="card-label">AVERAGE RATING</span></div>
                    <div class="card-value">{{ $stats['avgRating'] ? $stats['avgRating'].' / 5' : '–' }}</div>
                    <p class="card-description">
                        {{ $stats['ratingsCount'] > 0 ? 'from '.number_format($stats['ratingsCount']).' '.\Illuminate\Support\Str::plural('rating', $stats['ratingsCount']) : 'No ratings yet' }}
                    </p>
                </div>

                <a class="card" href="{{ route('admin.skills') }}">
                    <div class="card-top"><span class="card-label">SKILLS</span></div>
                    <div class="card-value">{{ number_format($stats['skillCount']) }}</div>
                    <p class="card-description">in {{ number_format($stats['categoryCount']) }} categories</p>
                </a>

                <a class="card" href="{{ route('admin.lessons') }}">
                    <div class="card-top"><span class="card-label">PUBLISHED LESSONS</span></div>
                    <div class="card-value">{{ number_format($stats['publishedLessons']) }}</div>
                    <p class="card-description">live in the app</p>
                </a>

                <a class="card" href="{{ route('admin.reports') }}">
                    <div class="card-top"><span class="card-label">OPEN REPORTS</span></div>
                    <div class="card-value">{{ number_format($stats['pendingReports']) }}</div>
                    <p class="card-description">{{ $stats['pendingReports'] > 0 ? 'waiting for a decision' : 'nothing to review' }}</p>
                </a>

                <a class="card" href="{{ route('admin.exchanges', ['status' => 'disputed']) }}">
                    <div class="card-top"><span class="card-label">DISPUTES</span></div>
                    <div class="card-value">{{ number_format($stats['exchangeStatuses']['disputed']) }}</div>
                    <p class="card-description">{{ $stats['exchangeStatuses']['disputed'] > 0 ? 'exchanges a user reported' : 'no disputes' }}</p>
                </a>

                <a class="card" href="{{ route('admin.users', ['status' => 'active']) }}">
                    <div class="card-top"><span class="card-label">ONLINE NOW</span></div>
                    <div class="card-value">{{ number_format($stats['onlineNow']) }}</div>
                    <p class="card-description">users using the app right now</p>
                </a>

                <a class="card" href="{{ route('admin.users', ['status' => 'suspended']) }}">
                    <div class="card-top"><span class="card-label">SUSPENDED</span></div>
                    <div class="card-value">{{ number_format($stats['suspendedMembers']) }}</div>
                    <p class="card-description">{{ $stats['suspendedMembers'] > 0 ? 'users who cannot sign in' : 'nobody is suspended' }}</p>
                </a>

                <a class="card" href="{{ route('admin.comments') }}">
                    <div class="card-top"><span class="card-label">COMMENTS</span></div>
                    <div class="card-value">{{ number_format($stats['commentsRange']) }}</div>
                    <p class="card-description">in the last {{ $stats['days'] }} days &middot; {{ number_format($stats['commentsTotal']) }} in total</p>
                </a>

                <a class="card" href="{{ route('admin.listings') }}">
                    <div class="card-top"><span class="card-label">NEW LISTINGS</span></div>
                    <div class="card-value">{{ number_format($stats['newListingsRange']) }}</div>
                    <p class="card-description">posted in the last {{ $stats['days'] }} days</p>
                </a>
            </div>

            {{-- ===== Charts ===== --}}
            <div class="dash-grid">

                <div class="dash-panel">
                    <div class="dash-panel-head">
                        <h3>New users</h3>
                        <span>{{ $stats['newMembersRange'] }} in the last {{ $stats['days'] }} days</span>
                    </div>
                    <?php
                        $now = $stats['newMembersRange'];
                        $before = $stats['newMembersPrevious'];
                        $change = $before > 0 ? round(($now - $before) / $before * 100) : null;
                    ?>
                    <p class="dash-trend">
                        @if($change === null)
                            {{ $now > 0 ? 'No new users in the '.$stats['days'].' days before.' : 'No new users in either period.' }}
                        @elseif($change > 0)
                            <span class="dash-up">&#9650; {{ $change }}%</span> more than the {{ $stats['days'] }} days before ({{ $before }})
                        @elseif($change < 0)
                            <span class="dash-down">&#9660; {{ abs($change) }}%</span> fewer than the {{ $stats['days'] }} days before ({{ $before }})
                        @else
                            Same as the {{ $stats['days'] }} days before ({{ $before }})
                        @endif
                    </p>
                    @php($maxSignup = max(1, max(array_column($stats['signups'], 'count'))))
                    <div class="dash-bars">
                        @foreach($stats['signups'] as $day)
                            <div class="dash-bar" title="{{ $day['label'] }}: {{ $day['count'] }} new">
                                <span class="dash-bar-count">{{ $day['count'] ?: '' }}</span>
                                <div class="dash-bar-fill {{ $day['count'] === 0 ? 'empty' : '' }}" style="height: {{ max(4, round($day['count'] / $maxSignup * 100)) }}%"></div>
                                <span class="dash-bar-day">{{ $day['day'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="dash-panel">
                    <div class="dash-panel-head">
                        <h3>Exchanges by status</h3>
                        <span>{{ number_format($stats['totalExchanges']) }} total</span>
                    </div>
                    @if($stats['totalExchanges'] > 0)
                        <div class="dash-stack">
                            @foreach($stats['exchangeStatuses'] as $status => $count)
                                @if($count > 0)
                                    <div class="dash-seg st-{{ $status }}" style="flex: {{ $count }}" title="{{ ucfirst($status) }}: {{ $count }}"></div>
                                @endif
                            @endforeach
                        </div>
                        <ul class="dash-legend">
                            @foreach($stats['exchangeStatuses'] as $status => $count)
                                <li>
                                    <span class="dash-dot st-{{ $status }}"></span>
                                    <span class="dash-legend-name">{{ ucfirst($status) }}</span>
                                    <strong>{{ number_format($count) }}</strong>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="dash-empty">No exchanges yet. They will show up here once users start trading skills.</p>
                    @endif
                </div>

            </div>

            {{-- ===== Weekly engagement ===== --}}
            @php($weeks = $stats['engagement']['weeks'])
            @php($maxEng = max(1, max(array_merge([1], array_column($weeks, 'lessons'), array_column($weeks, 'exchanges')))))
            <div class="dash-panel" style="margin-bottom:22px;">
                <div class="dash-panel-head">
                    <h3>Weekly engagement</h3>
                    <span>{{ $stats['engagement']['lessonsTotal'] }} lessons passed &middot; {{ $stats['engagement']['exchangesTotal'] }} exchanges completed</span>
                </div>
                <div class="dash-pairs">
                    @foreach($weeks as $week)
                        <div class="dash-pair" title="Week of {{ $week['label'] }}: {{ $week['lessons'] }} lessons passed, {{ $week['exchanges'] }} exchanges completed">
                            <div class="dash-pair-bars">
                                <div class="a {{ $week['lessons'] === 0 ? 'zero' : '' }}" style="height: {{ max(3, round($week['lessons'] / $maxEng * 100)) }}%"></div>
                                <div class="b {{ $week['exchanges'] === 0 ? 'zero' : '' }}" style="height: {{ max(3, round($week['exchanges'] / $maxEng * 100)) }}%"></div>
                            </div>
                            <span class="dash-pair-label">{{ $week['label'] }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="dash-key">
                    <span><i style="background:var(--ll-forest)"></i>Lessons passed</span>
                    <span><i style="background:#55A630"></i>Exchanges completed</span>
                    <span>each bar group = one week, starting on the date shown</span>
                </div>
            </div>

            {{-- ===== Skills in demand ===== --}}
            <div class="dash-grid">

                @foreach([
                    ['title' => 'Most wanted skills', 'sub' => 'what users want to learn', 'rows' => $stats['topWanted']],
                    ['title' => 'Most offered skills', 'sub' => 'what users can teach', 'rows' => $stats['topOffered']],
                ] as $block)
                    <div class="dash-panel">
                        <div class="dash-panel-head">
                            <h3>{{ $block['title'] }}</h3>
                            <span>{{ $block['sub'] }}</span>
                        </div>
                        @php($max = max(1, ...array_merge([1], array_column($block['rows'], 'count'))))
                        @forelse($block['rows'] as $row)
                            <div class="dash-rank">
                                <div class="dash-rank-top"><span>{{ $row['name'] }}</span><strong>{{ $row['count'] }}</strong></div>
                                <div class="dash-rank-track"><div class="dash-rank-fill" style="width: {{ round($row['count'] / $max * 100) }}%"></div></div>
                            </div>
                        @empty
                            <p class="dash-empty">Nothing yet. Users add skills on their profile.</p>
                        @endforelse
                    </div>
                @endforeach

            </div>

            {{-- ===== Categories + newest members ===== --}}
            <div class="dash-grid">

                <div class="dash-panel">
                    <div class="dash-panel-head">
                        <h3>Top categories</h3>
                        <span>by number of listings</span>
                    </div>
                    @php($maxCat = max(1, ...array_merge([1], array_column($stats['topCategories'], 'count'))))
                    @forelse($stats['topCategories'] as $row)
                        <div class="dash-rank">
                            <div class="dash-rank-top"><span>{{ $row['name'] }}</span><strong>{{ $row['count'] }}</strong></div>
                            <div class="dash-rank-track"><div class="dash-rank-fill alt" style="width: {{ round($row['count'] / $maxCat * 100) }}%"></div></div>
                        </div>
                    @empty
                        <p class="dash-empty">No listings with a category yet.</p>
                    @endforelse
                </div>

                <div class="dash-panel">
                    <div class="dash-panel-head">
                        <h3>Newest users</h3>
                        <a href="{{ route('admin.users') }}">View all</a>
                    </div>
                    @forelse($stats['recentMembers'] as $member)
                        <a class="dash-member" href="{{ route('admin.users.show', $member) }}">
                            @include('partials.avatar', ['user' => $member, 'class' => 'dash-avatar'])
                            <span class="dash-member-text">
                                <strong>{{ $member->name }}@if($member->status === 'suspended') <em class="dash-susp">Suspended</em>@endif</strong>
                                <small>{{ $member->email }}</small>
                            </span>
                            <span class="dash-member-time">{{ $member->created_at->diffForHumans(['short' => true]) }}</span>
                        </a>
                    @empty
                        <p class="dash-empty">No users yet.</p>
                    @endforelse
                </div>

            </div>

            {{-- ===== Recent activity ===== --}}
            <div class="recent-activity">
                <div class="activity-header">
                    <div>
                        <span class="section-label">ACTIVITY</span>
                        <h2>Recent Activity</h2>
                    </div>
                    <a class="dash-link" href="{{ route('admin.activity') }}">View all</a>
                </div>

                @forelse($stats['activities'] as $activity)
                    <?php
                        $kind = \Illuminate\Support\Str::contains($activity->action, ['deleted', 'suspended', 'rejected', 'removed']) ? 'bad'
                            : (\Illuminate\Support\Str::contains($activity->action, ['activated', 'approved', 'created', 'resolved', 'enabled']) ? 'good' : 'info');
                    ?>
                    <div class="activity-item">
                        <div class="activity-indicator ai-{{ $kind }}"></div>
                        <div class="activity-text">
                            <strong>{{ $activity->description }}</strong>
                            <p>{{ $activity->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                @empty
                    <div class="activity-item">
                        <div class="activity-indicator"></div>
                        <div class="activity-text">
                            <strong>No recent activity</strong>
                            <p>Admin actions such as approving a listing will be listed here.</p>
                        </div>
                    </div>
                @endforelse
            </div>

        </section>
    </main>
</div>

</body>
</html>