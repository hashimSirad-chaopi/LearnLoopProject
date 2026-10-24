{{--
    A member's picture, with their initials as the fallback.
    Usage: @include('partials.avatar', ['user' => $member, 'class' => 'dash-avatar', 'letters' => 1])
    The initials are always drawn underneath, so a missing or broken image
    (not uploaded yet, storage link missing) still shows something sensible.
--}}
@php
    $letters = $letters ?? 1;
    $initials = mb_strtoupper(mb_substr(trim($user->name ?? '?'), 0, $letters));
@endphp
<span class="{{ $class ?? 'user-avatar' }} has-avatar">
    <span class="avatar-initials">{{ $initials }}</span>
    @if($user->avatar_url)
        <img class="avatar-img" src="{{ $user->avatar_url }}" alt="{{ $user->name }}" loading="lazy" onerror="this.remove()">
    @endif
</span>
