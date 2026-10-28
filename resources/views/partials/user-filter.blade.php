@php
    $filterUser = request('user') ? \App\Models\User::find((int) request('user')) : null;
    $filterLabels = ['listing' => 'listings', 'comments' => 'comments and replies', 'exchanges' => 'exchanges', 'reports' => 'reports against'];
    $filterRoutes = ['listing' => 'admin.listings', 'comments' => 'admin.comments', 'exchanges' => 'admin.exchanges', 'reports' => 'admin.reports'];
@endphp
@if($filterUser)
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;margin:0 0 14px;padding:10px 14px;border-radius:12px;background:#EAF8DC;border:1px solid #B7D99A;color:#16231C;font-size:14px;">
        <span>Showing {{ $filterLabels[$what] ?? 'items' }} for <strong>{{ $filterUser->name }}</strong></span>
        <span style="display:flex;gap:12px;">
            <a class="cell-link" href="{{ route('admin.users.show', $filterUser->id) }}">Back to user</a>
            <a class="cell-link" href="{{ route($filterRoutes[$what] ?? 'admin.home') }}">Show everyone</a>
        </span>
    </div>
@endif
