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
                    Listings go live as soon as members post them. Watch the feed and take down anything that breaks the rules.
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
                            Disable or delete a listing and tell the member why.
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
        <option value="active" @selected($status === 'active')>Live</option>
        <option value="disabled" @selected($status === 'disabled')>Disabled by admin</option>
        <option value="closed" @selected($status === 'closed')>Closed by member</option>
    </select>
    <select name="sort" class="sort-select" aria-label="Sort listings" onchange="this.form.submit()">
        <option value="newest" @selected($sort === 'newest')>Newest first</option>
        <option value="oldest" @selected($sort === 'oldest')>Oldest first</option>
        <option value="title" @selected($sort === 'title')>Title A to Z</option>
        <option value="status" @selected($sort === 'status')>Disabled first</option>
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
                                <button type="submit" name="bulk" value="restore" class="table-btn" onclick="return confirm('Put the selected disabled listings back in the feed?');">Restore selected</button>
                                <button type="button" class="table-btn" data-lm-open data-lm-bulk="disable" data-lm-title="the selected listings">Disable selected</button>
                                <button type="button" class="delete-btn" data-lm-open data-lm-bulk="delete" data-lm-title="the selected listings">Delete selected</button>
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
            @php($disabled = $listing->isDisabledByAdmin())
            <span class="listing-status {{ $disabled ? 'disabled' : $listing->status }}">{{ $disabled ? 'Disabled by admin' : ($listing->status === 'active' ? 'Live' : 'Closed by member') }}</span>
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
            @if($disabled)
                <p class="listing-reason"><strong>Reason:</strong> {{ $listing->disabled_reason }} <span class="ll-muted">&middot; {{ $listing->moderated_at->diffForHumans() }}</span></p>
            @endif
        </div>

        <div class="listing-footer">
            <span class="listing-date">Posted {{ $listing->created_at->diffForHumans() }}</span>
            <div class="listing-actions">
                <a href="{{ route('admin.listings.edit', $listing->id) }}" class="table-btn" style="text-decoration:none;">Edit</a>
                @if($disabled)
                    <form method="POST" action="{{ route('admin.listings.enable', $listing->id) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="disable-btn">Restore</button>
                    </form>
                @else
                    <button type="button" class="disable-btn" data-lm-open data-lm-url="{{ route('admin.listings.disable', $listing->id) }}" data-lm-method="PATCH" data-lm-mode="disable" data-lm-title="{{ $listing->title }}">Disable</button>
                @endif
                <button type="button" class="delete-btn" data-lm-open data-lm-url="{{ route('admin.listings.delete', $listing->id) }}" data-lm-method="DELETE" data-lm-mode="delete" data-lm-title="{{ $listing->title }}">Delete</button>
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

@include('partials.listing-moderation-dialog')

</body>
</html>