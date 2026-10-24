<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnLoop - User Management</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.admin-filters')
</head>
<body>

<div class="admin-container">

    @include('partials.admin-sidebar')

    <main class="main-content" data-live>

        <header class="top-header">
            <div class="header-left">
                <span class="header-label">ADMIN PANEL</span>
                <h1>User Management</h1>
                <p>Find members, review their accounts, and suspend or reactivate them.</p>
            </div>
        </header>

        <section class="users-page">
            <div class="ll-scroll">
                <div class="ll-page">

                    @include('partials.flash')
                    @if($errors->any())
                        <div class="um-alert">{{ $errors->first() }}</div>
                    @endif

                    {{-- ===== Summary (each card is also a filter) ===== --}}
                    <div class="um-stats">
                        <a class="um-stat {{ $status === 'all' ? 'active' : '' }}" href="{{ route('admin.users', array_filter(['search' => $search])) }}">
                            <span class="um-stat-label">All accounts</span>
                            <strong>{{ number_format($counts['all']) }}</strong>
                        </a>
                        <a class="um-stat {{ $status === 'active' ? 'active' : '' }}" href="{{ route('admin.users', array_filter(['search' => $search, 'status' => 'active'])) }}">
                            <span class="um-stat-label">Active</span>
                            <strong>{{ number_format($counts['active']) }}</strong>
                        </a>
                        <a class="um-stat {{ $status === 'offline' ? 'active' : '' }}" href="{{ route('admin.users', array_filter(['search' => $search, 'status' => 'offline'])) }}">
                            <span class="um-stat-label">Offline</span>
                            <strong>{{ number_format($counts['offline']) }}</strong>
                        </a>
                        <a class="um-stat {{ $status === 'suspended' ? 'active' : '' }}" href="{{ route('admin.users', array_filter(['search' => $search, 'status' => 'suspended'])) }}">
                            <span class="um-stat-label">Suspended</span>
                            <strong>{{ number_format($counts['suspended']) }}</strong>
                        </a>
                        <div class="um-stat plain">
                            <span class="um-stat-label">Joined this week</span>
                            <strong>{{ number_format($counts['week']) }}</strong>
                        </div>
                    </div>

                    {{-- ===== Search + add ===== --}}
                    <div class="ll-toolbar">
                        <form method="GET" action="{{ route('admin.users') }}" class="ll-search">
                            @if($status !== 'all')<input type="hidden" name="status" value="{{ $status }}">@endif
                            @if($sort !== 'created_at' || $dir !== 'desc')
                                <input type="hidden" name="sort" value="{{ $sort }}">
                                <input type="hidden" name="dir" value="{{ $dir }}">
                            @endif
                            <input type="search" name="search" value="{{ $search }}" placeholder="Search by name or email" aria-label="Search users">
                            <button type="submit" class="table-btn">Search</button>
                            @if($search !== '')
                                <a href="{{ route('admin.users', array_filter(['status' => $status !== 'all' ? $status : null])) }}" class="table-btn ll-link">Clear</a>
                            @endif
                        </form>
                        <span style="display:flex;gap:8px;align-items:center;">
                            <a href="{{ route('admin.users.export', array_filter(['search' => $search, 'status' => $status !== 'all' ? $status : null])) }}" class="table-btn ll-link">Export CSV</a>
                            <a href="{{ route('admin.users.create') }}" class="settings-save-btn ll-link">+ Add user</a>
                        </span>
                    </div>

                    <span class="ll-muted">
                        {{ $users->total() }} {{ \Illuminate\Support\Str::plural('account', $users->total()) }}
                        @if($status !== 'all') &middot; {{ $status }} only @endif
                        @if($search !== '') &middot; matching &ldquo;{{ $search }}&rdquo; @endif
                    </span>

                    {{-- ===== Bulk bar ===== --}}
                    @if($users->isNotEmpty())
                        <form id="bulk-users" method="POST" action="{{ route('admin.users.bulk') }}" class="bulk-bar">
                            @csrf
                            <label class="bulk-all"><input type="checkbox" data-select-all="bulk-users"> Select all on this page</label>
                            <span class="bulk-count"></span>
                            <span class="bulk-actions">
                                <select name="duration" class="lr-select" aria-label="Suspend selected members for">
                                    @foreach(\App\Support\Suspension::OPTIONS as $key => $label)
                                        <option value="{{ $key }}" {{ $key === '1w' ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <select name="reason" class="lr-select" aria-label="Reason for suspending">
                                    @foreach(\App\Support\Suspension::REASONS as $key => $label)
                                        <option value="{{ $key }}" {{ $key === 'rules' ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="note" class="lr-select" maxlength="500" placeholder='Details (required for "Other")' aria-label="Suspension details">
                                <button type="submit" name="bulk" value="suspend" class="table-btn" onclick="return confirm('Suspend the selected members for the chosen time? They will be signed out of the app and told by notification.');">Suspend selected</button>
                                <button type="submit" name="bulk" value="activate" class="table-btn" onclick="return confirm('Reactivate the selected members?');">Reactivate selected</button>
                            </span>
                        </form>
                    @endif

                    {{-- ===== Table ===== --}}
                    <div class="users-table-container um-table-wrap">
                        <table class="users-table um-table">
                            <thead>
                                <tr>
                                    <th class="um-check"></th>
                                    @foreach(['name' => 'Member', 'role' => 'Role', 'status' => 'Status'] as $col => $label)
                                        <th>
                                            <a class="th-sort {{ $sort === $col ? 'active' : '' }}"
                                               href="{{ route('admin.users', array_filter(['search' => $search, 'status' => $status !== 'all' ? $status : null, 'sort' => $col, 'dir' => ($sort === $col && $dir === 'asc') ? 'desc' : 'asc'])) }}">
                                                {{ $label }}<span class="arrow">{{ $sort === $col ? ($dir === 'asc' ? '▲' : '▼') : '↕' }}</span>
                                            </a>
                                        </th>
                                    @endforeach
                                    <th class="um-hide-sm">Listings</th>
                                    <th class="um-hide-sm">Reports</th>
                                    <th class="um-hide-sm">
                                        <a class="th-sort {{ $sort === 'created_at' ? 'active' : '' }}"
                                           href="{{ route('admin.users', array_filter(['search' => $search, 'status' => $status !== 'all' ? $status : null, 'sort' => 'created_at', 'dir' => ($sort === 'created_at' && $dir === 'asc') ? 'desc' : 'asc'])) }}">
                                            Joined<span class="arrow">{{ $sort === 'created_at' ? ($dir === 'asc' ? '▲' : '▼') : '↕' }}</span>
                                        </a>
                                    </th>
                                    <th class="um-actions-col">Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($users as $user)
                                    @php($isSelf = $user->id === auth()->id())
                                    @php($locked = $isSelf || $user->role === 'admin')
                                    @php($reports = (int) ($reportCounts[$user->id] ?? 0))
                                    <tr>
                                        <td class="um-check">
                                            @unless($locked)
                                                <input type="checkbox" name="ids[]" value="{{ $user->id }}" form="bulk-users" class="bulk-check" aria-label="Select {{ $user->name }}">
                                            @endunless
                                        </td>

                                        <td>
                                            <a class="user-cell um-member" href="{{ route('admin.users.show', $user->id) }}">
                                                @include('partials.avatar', ['user' => $user])
                                                <span class="um-member-text">
                                                    <strong>{{ $user->name }} @if($isSelf)<em class="um-you">you</em>@endif</strong>
                                                    <small>{{ $user->email }}</small>
                                                </span>
                                            </a>
                                        </td>

                                        <td><span class="role-badge">{{ ucfirst($user->role ?? 'user') }}</span></td>

                                        <td>
                                            @if($user->status === 'suspended')
                                                <span class="status-badge suspended-status">Suspended</span>
                                                <small class="lr-until">{{ $user->suspended_until ? 'until '.$user->suspended_until->format('M j, Y g:i A') : 'until reactivated' }}</small>
                                                @if($why = \App\Support\Suspension::describe($user->suspension_reason, $user->suspension_note))
                                                    <small class="lr-until" title="{{ $why }}">Reason: {{ \Illuminate\Support\Str::limit($why, 48) }}</small>
                                                @endif
                                            @elseif($user->isOnline())
                                                <span class="status-badge active-status">Active</span>
                                                <small class="lr-until">online now</small>
                                            @else
                                                <span class="status-badge offline-status">Offline</span>
                                                <small class="lr-until">{{ $user->last_seen_at ? 'last seen '.$user->last_seen_at->diffForHumans() : 'not seen yet' }}</small>
                                            @endif
                                        </td>

                                        <td class="um-hide-sm">{{ (int) ($listingCounts[$user->id] ?? 0) }}</td>
                                        <td class="um-hide-sm">
                                            @if($reports > 0)<span class="um-flag" title="Reports against this member that an admin upheld">{{ $reports }}</span>@else<span class="ll-muted">0</span>@endif
                                        </td>
                                        <td class="um-hide-sm">{{ $user->created_at?->format('M j, Y') }}</td>

                                        <td class="um-actions-col">
                                            <div class="um-actions">
                                                <a href="{{ route('admin.users.show', $user->id) }}" class="um-btn" title="View profile" aria-label="View {{ $user->name }}">
                                                    @include('partials.icon', ['name' => 'eye'])<span class="label">View</span>
                                                </a>
                                                <a href="{{ route('admin.users.edit', $user->id) }}" class="um-btn" title="Edit details" aria-label="Edit {{ $user->name }}">
                                                    @include('partials.icon', ['name' => 'edit'])<span class="label">Edit</span>
                                                </a>
                                                @unless($locked)
                                                    @if($user->status === 'suspended')
                                                        <form method="POST" action="{{ route('admin.users.activate', $user->id) }}">
                                                            @csrf @method('PATCH')
                                                            <button type="submit" class="um-btn good" title="Reactivate account" aria-label="Reactivate {{ $user->name }}">
                                                                @include('partials.icon', ['name' => 'check'])<span class="label">Reactivate</span>
                                                            </button>
                                                        </form>
                                                    @else
                                                        <button type="button" class="um-btn warn" title="Suspend account" aria-label="Suspend {{ $user->name }}"
                                                                data-suspend-open data-suspend-url="{{ route('admin.users.suspend', $user->id) }}" data-suspend-name="{{ $user->name }}">
                                                            @include('partials.icon', ['name' => 'ban'])<span class="label">Suspend</span>
                                                        </button>
                                                    @endif
                                                @endunless
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="empty-users">
                                            {{ $search !== '' || $status !== 'all' ? 'No accounts match these filters.' : 'No users yet.' }}
                                            @if($search !== '' || $status !== 'all')
                                                <a href="{{ route('admin.users') }}">Clear filters</a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $users->links('vendor.pagination.admin') }}

                </div>
            </div>
        </section>
    </main>
</div>

@include('partials.suspend-dialog')

</body>
</html>
