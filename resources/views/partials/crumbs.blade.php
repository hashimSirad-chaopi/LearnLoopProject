{{-- Usage: @include('partials.crumbs', ['items' => [['Categories', route('admin.categories')], ['Language', $url], ['Current page']]]) --}}
<nav class="ll-crumbs" aria-label="Breadcrumb">
    @foreach($items as $item)
        @if(! $loop->last && isset($item[1]))
            <a href="{{ $item[1] }}">{{ $item[0] }}</a><span>&rsaquo;</span>
        @else
            <strong>{{ $item[0] }}</strong>
        @endif
    @endforeach
</nav>
