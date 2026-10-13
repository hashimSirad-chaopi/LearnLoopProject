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
<form method="GET" action="{{ route('admin.listings') }}" style="display:flex; gap:10px; flex:1; align-items:center;">
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

<a href="{{ route('admin.listings.create') }}" class="settings-save-btn" style="text-decoration:none;">+ Add Listing</a>
                
            </div>


            <!-- =================================
                 SCROLLABLE LISTINGS
            ================================== -->
            <div class="listings-scroll-area">

                <div class="listings-feed">


                    <!-- =================================
                         LISTING 1
                    ================================== -->
                    
@if ($errors->has('listing'))
    <div role="alert" style="color: #b91c1c; margin: 12px 0;">
        {{ $errors->first('listing') }}
    </div>
@endif
                   @forelse($listings as $listing)
    <article class="listing-post">
        <div class="listing-post-header">
            <div class="listing-user">
                <div class="listing-avatar">{{ strtoupper(substr($listing->user->name, 0, 2)) }}</div>
                <div>
                    <h3>{{ $listing->user->name }}</h3>
                    <span>{{ $listing->category?->name ?? 'Uncategorized' }}</span>
                </div>
            </div>
            <span class="listing-status {{ $listing->status }}">{{ ucfirst($listing->status) }}</span>
        </div>

        
<div class="listing-content">
    <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:8px;">
        <span class="listing-category">
            {{ $listing->subcategory?->name ?? ($listing->category?->name ?? 'Uncategorized') }}
        </span>

        @if ($listing->level)
            <span class="listing-category">
                {{ ucfirst($listing->level) }}
            </span>
        @endif
    </div>

    <h2>{{ $listing->title }}</h2>
    <p>{{ $listing->description }}</p>
</div>

        <div class="listing-footer">
            <span class="listing-date">Posted {{ $listing->created_at->diffForHumans() }}</span>
            <div class="listing-actions">
                <a href="{{ route('admin.listings.edit', $listing->id) }}" class="table-btn" style="text-decoration:none;">Edit</a>
               
@if ($listing->status === 'pending')
    <form method="POST" action="{{ route('admin.listings.approve', $listing->id) }}">
        @csrf
        @method('PATCH')
        <button type="submit" class="table-btn">Approve</button>
    </form>

    <form method="POST" action="{{ route('admin.listings.reject', $listing->id) }}"
          onsubmit="return confirm('Reject this listing?');">
        @csrf
        @method('PATCH')
        <button type="submit" class="disable-btn">Reject</button>
    </form>
@elseif ($listing->status === 'active')
    <form method="POST" action="{{ route('admin.listings.disable', $listing->id) }}"
          onsubmit="return confirm('Disable this listing?');">
        @csrf
        @method('PATCH')
        <button type="submit" class="disable-btn">Disable</button>
    </form>
@else
    <form method="POST" action="{{ route('admin.listings.enable', $listing->id) }}">
        @csrf
        @method('PATCH')
        <button type="submit" class="disable-btn">Enable</button>
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