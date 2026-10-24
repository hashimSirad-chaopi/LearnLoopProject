{{-- Small inline icons (stroke follows the text colour). Usage: @include('partials.icon', ['name' => 'eye']) --}}
<svg class="ll-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('eye')
            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>
            @break
        @case('edit')
            <path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
            @break
        @case('ban')
            <circle cx="12" cy="12" r="9"/><path d="M5.6 5.6l12.8 12.8"/>
            @break
        @case('check')
            <circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.7 2.7L16 9.8"/>
            @break
    @endswitch
</svg>
