<aside class="sidebar">
    <div class="sidebar-top">
        <div class="logo">LearnLoop</div>
        <nav class="sidebar-nav">
            <a href="{{ route('admin.home') }}" class="nav-item {{ request()->routeIs('admin.home') ? 'active' : '' }}">Dashboard</a>
            <a href="{{ route('admin.users') }}" class="nav-item {{ request()->routeIs('admin.users*') ? 'active' : '' }}">User Management</a>
            <a href="{{ route('admin.listings') }}" class="nav-item {{ request()->routeIs('admin.listings*') ? 'active' : '' }}">Listings</a>
            <a href="{{ route('admin.categories') }}" class="nav-item {{ request()->routeIs('admin.categories*') ? 'active' : '' }}">Categories</a>
            <a href="{{ route('admin.subcategories') }}" class="nav-item {{ request()->routeIs('admin.subcategories*') ? 'active' : '' }}"> Subcategories </a>
            <a href="{{ route('admin.exchanges') }}" class="nav-item {{ request()->routeIs('admin.exchanges*') ? 'active' : '' }}">Exchange Monitoring</a>
            <a href="{{ route('admin.reports') }}" class="nav-item {{ request()->routeIs('admin.reports*') ? 'active' : '' }}">Reports</a>
            <a href="{{ route('admin.activity') }}" class="nav-item {{ request()->routeIs('admin.activity*') ? 'active' : '' }}">Activity Log</a>
            <a href="{{ route('admin.settings') }}" class="nav-item {{ request()->routeIs('admin.settings*') ? 'active' : '' }}">Settings</a>
        </nav>
    </div>
    <div class="sidebar-bottom">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="logout-btn">Log Out</button>
        </form>
    </div>
</aside>