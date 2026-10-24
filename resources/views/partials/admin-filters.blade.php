{{-- One look for every admin search / filter bar, the same as Listings:
     rounded, 42px tall, soft shadow, mint focus ring.
     Usage: @include('partials.admin-filters') once in <head>, then put the controls inside class="ll-search". --}}
<style>
    .ll-search input[type="text"],
    .ll-search input[type="search"],
    .ll-search select {
        height: 42px;
        min-width: 220px;
        padding: 0 14px;
        border: 1px solid var(--ll-border-dark);
        border-radius: 10px;
        outline: none;
        background: var(--ll-surface);
        color: var(--ll-text);
        font: inherit;
        font-size: 12.5px;
        box-shadow: var(--ll-shadow-sm);
        transition: .2s ease;
    }
    .ll-search select { min-width: 0; padding: 0 12px; font-size: 13px; cursor: pointer; }
    .ll-search input::placeholder { color: var(--ll-muted-2); }
    .ll-search input:hover, .ll-search select:hover { border-color: #93A396; }
    .ll-search input:focus, .ll-search select:focus {
        border-color: var(--ll-mint-dark);
        box-shadow: 0 0 0 4px rgba(183, 255, 114, .18), var(--ll-shadow-sm);
    }
    .ll-search { gap: 10px; }
    .ll-search.grow { flex: 1; flex-wrap: nowrap; }
    .ll-search.grow input[type="search"], .ll-search.grow input[type="text"] { flex: 1; min-width: 0; }

    /* the small selects and boxes inside bulk bars (same as User Management) */
    .bulk-bar select, .bulk-bar input[type="text"] {
        height: 34px; padding: 0 10px;
        border: 1px solid var(--ll-border-dark); border-radius: 10px;
        background: var(--ll-surface); color: var(--ll-text); font: inherit; font-size: 12.5px; font-weight: 700;
    }
    .bulk-bar input[type="text"] { font-weight: 500; min-width: 190px; }
    .bulk-bar .bulk-actions { gap: 8px; }

    @media (max-width: 700px) {
        .ll-search input[type="text"], .ll-search input[type="search"], .ll-search select { min-width: 0; flex: 1 1 140px; }
    }
</style>
