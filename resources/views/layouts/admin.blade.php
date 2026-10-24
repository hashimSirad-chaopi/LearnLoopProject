<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>LearnLoop - @yield('title', 'Admin')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        // Applied before paint so there's no flash of the sidebar
        // re-appearing on every page load if the admin hid it.
        if (localStorage.getItem('ll-sidebar-hidden') === 'true') {
            document.documentElement.classList.add('sidebar-hidden');
        }
    </script>
</head>

<body>

<div class="admin-container">

    <!-- =========================================
         SIDEBAR
    ========================================== -->
    <aside class="sidebar">

        <div class="sidebar-top">

            <div class="logo">
                LearnLoop
            </div>

            <nav class="sidebar-nav">

                <a href="{{ route('admin.home') }}"
                   class="nav-item {{ request()->routeIs('admin.home') ? 'active' : '' }}">
                    Dashboard
                </a>

                <a href="{{ route('admin.users') }}"
                   class="nav-item {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                    User Management
                </a>

                <a href="{{ route('admin.listings') }}"
                   class="nav-item {{ request()->routeIs('admin.listings*') ? 'active' : '' }}">
                    Listings
                </a>

                <a href="{{ route('admin.categories.index') }}"
                   class="nav-item {{ request()->routeIs('admin.categories*') ? 'active' : '' }}">
                    Categories
                </a>

                <a href="{{ route('admin.exchanges.index') }}"
                   class="nav-item {{ request()->routeIs('admin.exchanges.*') ? 'active' : '' }}">
                    Exchanges
                </a>

                <a href="{{ route('admin.schedules.index') }}"
                   class="nav-item {{ request()->routeIs('admin.schedules.*') ? 'active' : '' }}">
                    Schedules
                </a>

                <a href="{{ route('admin.messages.index') }}"
                   class="nav-item {{ request()->routeIs('admin.messages.*') ? 'active' : '' }}">
                    Messages
                </a>

                <a href="{{ route('admin.notifications') }}"
                   class="nav-item {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">
                    Notifications
                </a>

                <a href="{{ route('admin.reports') }}"
                   class="nav-item {{ request()->routeIs('admin.reports*') ? 'active' : '' }}">
                    Reports
                </a>

                <a href="{{ route('admin.settings') }}"
                   class="nav-item {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                    Settings
                </a>

            </nav>

        </div>


        <!-- LOG OUT -->
        <div class="sidebar-bottom">

            @include('partials.logout-confirm')

        </div>

    </aside>


    <!-- =========================================
         MAIN CONTENT
    ========================================== -->
    <main class="main-content">

        <!-- FIXED HEADER -->
        <header class="top-header">

            <button type="button" id="sidebarToggleBtn" class="sidebar-toggle-btn" title="Show/hide sidebar">
                ☰
            </button>

            <div class="header-left">

                <span class="header-label">
                    ADMIN PANEL
                </span>

                <h1>
                    @yield('page-title')
                </h1>

                <p>
                    @yield('page-subtitle')
                </p>

            </div>

        </header>


        <!-- FLASH MESSAGES -->
        @if (session('success'))
            <div class="admin-flash admin-flash-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="admin-flash admin-flash-error">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="admin-flash admin-flash-error">
                <ul class="admin-flash-list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        @yield('content')

    </main>

</div>

<script>
    document.getElementById('sidebarToggleBtn').addEventListener('click', function () {
        const isHidden = document.documentElement.classList.toggle('sidebar-hidden');
        localStorage.setItem('ll-sidebar-hidden', isHidden);
    });
</script>

</body>
</html>