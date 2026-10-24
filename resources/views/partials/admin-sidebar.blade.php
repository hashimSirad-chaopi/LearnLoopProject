<button type="button" class="sidebar-toggle" aria-label="Open menu" onclick="document.body.classList.toggle('sidebar-open')">&#9776;</button>
<div class="sidebar-backdrop" onclick="document.body.classList.remove('sidebar-open')"></div>

<aside class="sidebar">
    <div class="sidebar-top">
        <div class="logo">LearnLoop</div>
        <nav class="sidebar-nav">
            <a href="{{ route('admin.home') }}" class="nav-item {{ request()->routeIs('admin.home') ? 'active' : '' }}">Dashboard</a>
            <a href="{{ route('admin.users') }}" class="nav-item {{ request()->routeIs('admin.users*') ? 'active' : '' }}">User Management</a>
            <a href="{{ route('admin.listings') }}" class="nav-item {{ request()->routeIs('admin.listings*') ? 'active' : '' }}">Listings</a>
            <a href="{{ route('admin.comments') }}" class="nav-item {{ request()->routeIs('admin.comments*') ? 'active' : '' }}">Comments</a>
            <?php $pendingLessonCount = \App\Models\Lesson::whereNotNull('author_id')->where('status', 'pending')->count(); ?>
            {{-- Skills & lessons and Member lessons are tabs inside this section, so one entry is enough. --}}
            <a href="{{ route('admin.categories') }}" class="nav-item {{ request()->routeIs('admin.categories*', 'admin.skills*', 'admin.lessons*') ? 'active' : '' }}">Categories
                @if($pendingLessonCount > 0)<span class="nav-count">{{ $pendingLessonCount }}</span>@endif
            </a>
            <a href="{{ route('admin.professional') }}" class="nav-item {{ request()->routeIs('admin.professional*') ? 'active' : '' }}">Professional Proofs
                @php($pendingProCount = \App\Models\ProfessionalVerification::where('status', 'in_review')->count())
                @if($pendingProCount > 0)<span class="nav-count">{{ $pendingProCount }}</span>@endif
            </a>
            <a href="{{ route('admin.exchanges') }}" class="nav-item {{ request()->routeIs('admin.exchanges*') ? 'active' : '' }}">Exchange Monitoring</a>
            <a href="{{ route('admin.reports') }}" class="nav-item {{ request()->routeIs('admin.reports*') ? 'active' : '' }}">Reports</a>
            <a href="{{ route('admin.appeals') }}" class="nav-item {{ request()->routeIs('admin.appeals*') ? 'active' : '' }}">Recovery Requests
                @php($pendingAppealCount = \App\Models\AccountAppeal::where('status', 'pending')->count())
                @if($pendingAppealCount > 0)<span class="nav-count">{{ $pendingAppealCount }}</span>@endif
            </a>
            <a href="{{ route('admin.notifications') }}" class="nav-item {{ request()->routeIs('admin.notifications*') ? 'active' : '' }}">Notifications
                @php($attentionTotal = \App\Support\AdminAlerts::total())
                @if($attentionTotal > 0)<span class="nav-count">{{ $attentionTotal }}</span>@endif
            </a>
            <a href="{{ route('admin.activity') }}" class="nav-item {{ request()->routeIs('admin.activity*') ? 'active' : '' }}">Activity Log</a>
            <a href="{{ route('admin.settings') }}" class="nav-item {{ request()->routeIs('admin.settings*') ? 'active' : '' }}">Settings</a>
            <a href="{{ route('admin.security') }}" class="nav-item {{ request()->routeIs('admin.security*') ? 'active' : '' }}">Security</a>
        </nav>
    </div>
    <div class="sidebar-bottom">
        @include('partials.logout-confirm')
    </div>
</aside>

@include('partials.live-updates')
