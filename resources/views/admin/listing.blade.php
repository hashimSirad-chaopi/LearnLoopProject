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
       @include('partials.admin-sidebar')


    <!-- =========================================
         MAIN CONTENT
    ========================================== -->
    <main class="main-content" data-live>

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
<form method="GET" action="{{ route('admin.listings') }}" style="display:flex; gap:10px; flex:1; align-items:center;">
    <input
        type="search"
        name="search"
        value="{{ $search }}"
        class="listing-search"
        placeholder="Search listings..."
        aria-label="Search listings"
    >
    <select name="status" class="sort-select" aria-label="Filter by status" onchange="this.form.submit()">
        <option value="all" @selected($status === 'all')>All statuses</option>
        <option value="pending" @selected($status === 'pending')>Pending approval</option>
        <option value="active" @selected($status === 'active')>Active</option>
        <option value="inactive" @selected($status === 'inactive')>Inactive</option>
    </select>
    <select name="sort" class="sort-select" aria-label="Sort listings" onchange="this.form.submit()">
        <option value="newest" @selected($sort === 'newest')>Newest first</option>
        <option value="oldest" @selected($sort === 'oldest')>Oldest first</option>
        <option value="title" @selected($sort === 'title')>Title A to Z</option>
        <option value="status" @selected($sort === 'status')>Pending first</option>
    </select>
    <button type="submit" class="table-btn">Search</button>
</form>

<a href="{{ route('admin.listings.create') }}" class="settings-save-btn" style="text-decoration:none;">+ Add Listing</a>
                
            </div>


            <!-- =================================
                 SCROLLABLE LISTINGS
            ================================== -->
            <div class="listings-scroll-area">

                <div class="listings-feed">

                    @include('partials.flash')

                    @if($listings->isNotEmpty())
                        <form id="bulk-listings" method="POST" action="{{ route('admin.listings.bulk') }}" class="bulk-bar">
                            @csrf
                            <label class="bulk-all"><input type="checkbox" data-select-all="bulk-listings"> Select all on this page ({{ $listings->count() }})</label>
                            <span class="bulk-count"></span>
                            <span class="bulk-actions">
                                <button type="submit" name="bulk" value="enable" class="table-btn" onclick="return confirm('Approve / enable the selected listings?');">Approve / enable selected</button>
                                <button type="submit" name="bulk" value="disable" class="table-btn" onclick="return confirm('Disable the selected listings?');">Disable selected</button>
                            </span>
                        </form>
                    @endif


                    <!-- =================================
                         LISTING 1
                    ================================== -->
                   @forelse($listings as $listing)
    <article class="listing-post">
        <div class="listing-post-header">
            <input type="checkbox" name="ids[]" value="{{ $listing->id }}" form="bulk-listings" class="bulk-check" aria-label="Select listing">
            <div class="listing-user">
                @include('partials.avatar', ['user' => $listing->user, 'class' => 'listing-avatar', 'letters' => 2])
                <div>
                    <h3>{{ $listing->user->name }}</h3>
                    <span>{{ $listing->category?->name ?? 'Uncategorized' }}</span>
                </div>
            </div>
            <span class="listing-status {{ $listing->status }}">{{ ucfirst($listing->status) }}</span>
        </div>

        <div class="listing-content">
            <span class="listing-category">
    {{ $listing->category?->name ?? 'Uncategorized' }}
</span>
            <h2>{{ $listing->title }}</h2>
            @if($listing->wantsSkill || $listing->offersSkill)
                <p style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
                    <span class="ll-badge">Offers: {{ $listing->offersSkill?->title ?? 'Nothing yet' }}</span>
                    <span class="ll-badge">Wants: {{ $listing->wantsSkill?->title ?? '—' }}</span>
                    @if($listing->wants_level)<span class="ll-badge">Level: {{ ucfirst($listing->wants_level) }}@if($listing->wants_level === 'professional') · paid{{ $listing->budget ? ' · ₱'.number_format($listing->budget).' per lesson' : ' · budget negotiable' }}@endif</span>@endif
                    @if($listing->isLearnerOnly())<span class="ll-badge learner-only">Learner only</span>@endif
                </p>
            @endif
            <p>{{ $listing->description }}</p>
        </div>

        <div class="listing-footer">
            <span class="listing-date">Posted {{ $listing->created_at->diffForHumans() }}</span>
            <div class="listing-actions">
                <a href="{{ route('admin.listings.edit', $listing->id) }}" class="table-btn" style="text-decoration:none;">Edit</a>
                @if($listing->status !== 'active')
    <form method="POST" action="{{ route('admin.listings.enable', $listing->id) }}">
        @csrf @method('PATCH')
        <button type="submit" class="disable-btn">{{ $listing->status === 'pending' ? 'Approve' : 'Enable' }}</button>
    </form>
@endif
@if($listing->status !== 'inactive')
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

                    {{ $listings->links('vendor.pagination.admin') }}

                </div>

            </div>

        </section>

    </main>

</div>

</body>
</html>