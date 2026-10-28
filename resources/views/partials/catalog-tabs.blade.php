{{-- Shared by Categories, Skills & Lessons and Lesson Review so the three feel like one section. --}}
@php($pendingMemberLessons = \App\Models\Lesson::whereNotNull('author_id')->where('status', 'pending')->count())

<div class="ll-catalog-nav">
    <div class="ll-tabs">
        <a class="ll-tab {{ request()->routeIs('admin.categories*') ? 'active' : '' }}" href="{{ route('admin.categories') }}">1. Categories</a>
        <a class="ll-tab {{ request()->routeIs('admin.skills*') ? 'active' : '' }}" href="{{ route('admin.skills') }}">2. Skills &amp; lessons</a>
        <a class="ll-tab {{ request()->routeIs('admin.lessons*') ? 'active' : '' }}" href="{{ route('admin.lessons') }}">
            3. User lessons
            @if($pendingMemberLessons > 0)<span class="nav-count">{{ $pendingMemberLessons }}</span>@endif
        </a>
    </div>
    <p class="ll-flow">
        <strong>How it fits together:</strong>
        a <em>category</em> groups skills &rarr; a <em>skill</em> has levels (easy to expert) &rarr; each level holds <em>lessons</em> &rarr; each lesson has a short quiz.
        Lessons users write themselves are reviewed in step 3.
    </p>
</div>
