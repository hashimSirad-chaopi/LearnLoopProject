@extends('layouts.admin')

@section('title', 'Schedules')
@section('page-title', 'Session Management')
@section('page-subtitle', 'Track and moderate scheduled skill-exchange sessions.')

@section('content')

@php
    $firstDay    = $month->copy()->startOfMonth();
    $daysInMonth = $month->daysInMonth;
    $leadingBlanks = (int) $firstDay->dayOfWeek; // 0 = Sunday
@endphp

<section class="admin-page">

    <!-- MONTH NAVIGATION -->
    <div class="admin-toolbar">

        <div class="dashboard-heading">
            <div>
                <span class="section-label">SESSIONS</span>
                <h2>{{ $month->format('F Y') }}</h2>
                <p>{{ $schedules->count() }} session{{ $schedules->count() === 1 ? '' : 's' }} this month.</p>
            </div>
        </div>

        <div class="month-nav">
            <a href="{{ route('admin.schedules.index', ['month' => $prevMonth, 'status' => $currentStatus]) }}"
               class="btn-small">&larr; Prev</a>

            <a href="{{ route('admin.schedules.index', ['status' => $currentStatus]) }}"
               class="btn-small">Today</a>

            <a href="{{ route('admin.schedules.index', ['month' => $nextMonth, 'status' => $currentStatus]) }}"
               class="btn-small">Next &rarr;</a>
        </div>

    </div>


    <!-- STATUS FILTER -->
    <div class="filter-tabs">
        @foreach ($statusOptions as $option)
            <a href="{{ route('admin.schedules.index', ['month' => $month->format('Y-m'), 'status' => $option]) }}"
               class="filter-tab {{ $currentStatus === $option ? 'active' : '' }}">
                {{ ucfirst($option) }}
            </a>
        @endforeach
    </div>


    <!-- CALENDAR -->
    <div class="calendar">

        <div class="calendar-head">
            @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dayName)
                <div class="calendar-dayname">{{ $dayName }}</div>
            @endforeach
        </div>

        <div class="calendar-grid">

            @for ($i = 0; $i < $leadingBlanks; $i++)
                <div class="calendar-cell calendar-cell-empty"></div>
            @endfor

            @for ($day = 1; $day <= $daysInMonth; $day++)
                @php
                    $cellDate = $month->copy()->day($day);
                    $daySessions = $byDay[$day] ?? collect();
                @endphp

                <div class="calendar-cell {{ $cellDate->isToday() ? 'is-today' : '' }}">

                    <span class="calendar-daynum">{{ $day }}</span>

                    @foreach ($daySessions as $session)
                        <a href="#session-{{ $session->id }}"
                           class="calendar-event event-{{ $session->status }}"
                           title="{{ $session->exchange->skill_offered ?? 'Session' }}">
                            {{ \Carbon\Carbon::parse($session->time_start)->format('H:i') }}
                            {{ $session->exchange->provider->name ?? 'Session' }}
                        </a>
                    @endforeach

                </div>
            @endfor

        </div>

    </div>


    <!-- UPCOMING -->
    @if ($upcoming->isNotEmpty())
        <div class="detail-section">
            <h2 class="section-title">Next up</h2>
            @foreach ($upcoming as $session)
                <div class="mini-row">
                    <div>
                        <strong>{{ \Carbon\Carbon::parse($session->date)->format('D, j M') }}</strong>
                        <span class="cell-muted">
                            {{ \Carbon\Carbon::parse($session->time_start)->format('H:i') }}
                        </span>
                        <span class="cell-muted">
                            &middot; {{ $session->exchange->provider->name ?? '?' }}
                            &amp; {{ $session->exchange->learner->name ?? '?' }}
                        </span>
                    </div>
                    <span class="status-badge status-{{ $session->status }}">
                        {{ ucfirst($session->status) }}
                    </span>
                </div>
            @endforeach
        </div>
    @endif


    <!-- SESSION LIST WITH ACTIONS -->
    <div class="detail-section">

        <h2 class="section-title">All sessions in {{ $month->format('F Y') }}</h2>

        @forelse ($schedules as $session)
            <div class="session-card" id="session-{{ $session->id }}">

                <div class="session-card-head">
                    <div>
                        <strong>{{ \Carbon\Carbon::parse($session->date)->format('D, j M Y') }}</strong>
                        <span class="cell-muted">
                            {{ \Carbon\Carbon::parse($session->time_start)->format('H:i') }}
                            &ndash;
                            {{ \Carbon\Carbon::parse($session->time_end)->format('H:i') }}
                        </span>
                    </div>
                    <span class="status-badge status-{{ $session->status }}">
                        {{ ucfirst($session->status) }}
                    </span>
                </div>

                <p class="cell-muted">
                    @if ($session->exchange)
                        <a href="{{ route('admin.exchanges.show', $session->exchange) }}" class="cell-link">
                            Exchange #{{ $session->exchange->id }}
                        </a>
                        &middot;
                        {{ $session->exchange->provider->name ?? 'Deleted user' }}
                        teaching
                        {{ $session->exchange->learner->name ?? 'Deleted user' }}
                    @else
                        Exchange no longer exists
                    @endif
                    @if ($session->location)
                        &middot; {{ $session->location }}
                    @endif
                </p>

                @if ($session->notes)
                    <p class="cell-muted">{{ $session->notes }}</p>
                @endif

                <div class="session-actions">

                    @foreach (['confirmed' => 'Confirm', 'completed' => 'Mark complete', 'cancelled' => 'Cancel'] as $value => $label)
                        @if ($session->status !== $value)
                            <form method="POST" action="{{ route('admin.schedules.updateStatus', $session) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="{{ $value }}">
                                <button type="submit" class="btn-small {{ $value === 'cancelled' ? 'btn-danger' : '' }}">
                                    {{ $label }}
                                </button>
                            </form>
                        @endif
                    @endforeach

                    <form method="POST" action="{{ route('admin.schedules.destroy', $session) }}"
                          onsubmit="return confirm('Delete this session permanently?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-small btn-danger">Delete</button>
                    </form>

                </div>


                <!-- Reschedule -->
                <details class="reschedule-box">
                    <summary>Reschedule</summary>

                    <form method="POST" action="{{ route('admin.schedules.reschedule', $session) }}" class="reschedule-form">
                        @csrf @method('PATCH')

                        <label>Date
                            <input type="date" name="date"
                                   value="{{ \Carbon\Carbon::parse($session->date)->format('Y-m-d') }}" required>
                        </label>

                        <label>Start
                            <input type="time" name="time_start"
                                   value="{{ \Carbon\Carbon::parse($session->time_start)->format('H:i') }}" required>
                        </label>

                        <label>End
                            <input type="time" name="time_end"
                                   value="{{ \Carbon\Carbon::parse($session->time_end)->format('H:i') }}" required>
                        </label>

                        <label>Location
                            <input type="text" name="location" value="{{ $session->location }}">
                        </label>

                        <button type="submit" class="btn-primary">Save new time</button>
                    </form>
                </details>

            </div>
        @empty
            <p class="empty-note">No sessions scheduled in this month.</p>
        @endforelse

    </div>

</section>

@endsection
