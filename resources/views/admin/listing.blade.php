<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>LearnLoop - Listings</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
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

                <a href="{{ route('admin.home') }}" class="nav-item">
                    Dashboard
                </a>

                <a href="{{ route('admin.users') }}" class="nav-item">
                    User Management
                </a>

                <a href="{{ route('admin.listings') }}" class="nav-item active">
                    Listings
                </a>

                <a href="{{ route('admin.exchanges') }}" class="nav-item">Exchange Monitoring</a>


                <a href="{{ route('admin.reports') }}" class="nav-item">
                    Reports
                </a>

                <a href="{{ route('admin.settings') }}" class="nav-item">
                    Settings
                </a>

            </nav>

        </div>


        <!-- LOG OUT -->
        <div class="sidebar-bottom">

            <form method="POST" action="{{ route('logout') }}">

                @csrf

                <button type="submit" class="logout-btn">
                    Log Out
                </button>

            </form>

        </div>

    </aside>


    <!-- =========================================
         MAIN CONTENT
    ========================================== -->
    <main class="main-content">

        <!-- FIXED HEADER -->
        <header class="top-header">

            <div class="header-left">

                <span class="header-label">
                    ADMIN PANEL
                </span>

                <h1>
                    Listings
                </h1>

                <p>
                    Review and manage listings posted by LearnLoop users.
                </p>

            </div>

        </header>


        <!-- =====================================
             LISTINGS PAGE
        ====================================== -->
        <section class="listing-page">

            <!-- TOOLBAR -->
            <div class="listing-toolbar">

                <div class="dashboard-heading">

                    <div>

                        <span class="section-label">
                            LISTINGS
                        </span>

                        <h2>
                            All Listings
                        </h2>

                        <p>
                            Review, disable, or remove user-created listings.
                        </p>

                    </div>

                </div>


                <!-- SEARCH -->
                <!-- SEARCH -->
<form method="GET" action="{{ route('admin.listings') }}" style="display:flex; gap:10px; flex:1;">
    <input
        type="search"
        name="search"
        value="{{ $search }}"
        class="listing-search"
        placeholder="Search listings..."
        aria-label="Search listings"
    >
    <button type="submit" class="table-btn">Search</button>
</form>

<a href="{{ route('admin.listings.create') }}" class="settings-save-btn" style="text-decoration:none; display:inline-block; padding:10px 18px;">+ Add Listing</a>
                <a href="{{ route('admin.categories') }}" class="table-btn" style="text-decoration:none; display:inline-block;">Manage Categories</a>
            </div>


            <!-- =================================
                 SCROLLABLE LISTINGS
            ================================== -->
            <div class="listings-scroll-area">

                <div class="listings-feed">


                    <!-- =================================
                         LISTING 1
                    ================================== -->
                   @forelse($listings as $listing)
    <article class="listing-post">
        <div class="listing-post-header">
            <div class="listing-user">
                <div class="listing-avatar">{{ strtoupper(substr($listing->user->name, 0, 2)) }}</div>
                <div>
                    <h3>{{ $listing->user->name }}</h3>
                    <span>{{ $listing->category }}</span>
                </div>
            </div>
            <span class="listing-status {{ $listing->status }}">{{ ucfirst($listing->status) }}</span>
        </div>

        <div class="listing-content">
            <span class="listing-category">{{ $listing->category }}</span>
            <h2>{{ $listing->title }}</h2>
            <p>{{ $listing->description }}</p>
        </div>

        <div class="listing-footer">
            <span class="listing-date">Posted {{ $listing->created_at->diffForHumans() }}</span>
            <div class="listing-actions">
                <a href="{{ route('admin.listings.edit', $listing->id) }}" class="table-btn" style="text-decoration:none; display:inline-block;">Edit</a>
                @if($listing->status === 'inactive')
    <form method="POST" action="{{ route('admin.listings.enable', $listing->id) }}">
        @csrf @method('PATCH')
        <button type="submit" class="disable-btn">Enable</button>
    </form>
@else
    <form method="POST" action="{{ route('admin.listings.disable', $listing->id) }}">
        @csrf @method('PATCH')
        <button type="submit" class="disable-btn">Disable</button>
    </form>
@endif
                <form method="POST" action="{{ route('admin.listings.delete', $listing->id) }}" onsubmit="return confirm('Delete this listing?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="delete-btn">Delete</button>
                </form>
            </div>
        </div>
    </article>
@empty
    <p>No listings found.</p>
@endforelse


                </div>

            </div>

        </section>

    </main>

</div>

</body>
</html>