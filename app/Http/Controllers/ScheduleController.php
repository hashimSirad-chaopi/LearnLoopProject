<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /** Statuses a session can be in. */
    public const STATUSES = ['pending', 'confirmed', 'completed', 'cancelled'];

    /**
     * Calendar for one month plus a list of that month's sessions.
     */
    public function index(Request $request)
    {
        // Which month are we looking at? Defaults to the current one.
        try {
            $month = Carbon::createFromFormat('Y-m', $request->get('month', now()->format('Y-m')))
                ->startOfMonth();
        } catch (\Exception $e) {
            $month = now()->startOfMonth();
        }

        $status = $request->get('status', 'all');

        $query = Schedule::with(['exchange.provider', 'exchange.learner'])
            ->whereBetween('date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()]);

        if ($status !== 'all' && in_array($status, self::STATUSES, true)) {
            $query->where('status', $status);
        }

        $schedules = $query->orderBy('date')->orderBy('time_start')->get();

        // Group by day-of-month so the calendar can look sessions up cheaply.
        $byDay = $schedules->groupBy(fn ($s) => Carbon::parse($s->date)->day);

        return view('admin.schedules.index', [
            'month'         => $month,
            'schedules'     => $schedules,
            'byDay'         => $byDay,
            'currentStatus' => $status,
            'statusOptions' => array_merge(['all'], self::STATUSES),
            'prevMonth'     => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth'     => $month->copy()->addMonth()->format('Y-m'),
            'upcoming'      => Schedule::with(['exchange.provider', 'exchange.learner'])
                                  ->whereDate('date', '>=', today())
                                  ->whereIn('status', ['pending', 'confirmed'])
                                  ->orderBy('date')
                                  ->orderBy('time_start')
                                  ->limit(5)
                                  ->get(),
        ]);
    }

    /**
     * Change a session's status (confirm, complete, cancel).
     */
    public function updateStatus(Request $request, Schedule $schedule)
    {
        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', self::STATUSES),
        ]);

        $schedule->update(['status' => $validated['status']]);

        return back()->with('success', 'Session #' . $schedule->id . ' marked as ' . $validated['status'] . '.');
    }

    /**
     * Move a session to a different date or time.
     */
    public function reschedule(Request $request, Schedule $schedule)
    {
        $validated = $request->validate([
            'date'       => 'required|date',
            'time_start' => 'required|date_format:H:i',
            'time_end'   => 'required|date_format:H:i|after:time_start',
            'location'   => 'nullable|string|max:255',
        ]);

        $schedule->update($validated + ['status' => 'pending']);

        return back()->with('success', 'Session #' . $schedule->id . ' rescheduled.');
    }

    /**
     * Remove a session entirely.
     */
    public function destroy(Schedule $schedule)
    {
        $schedule->delete();

        return redirect()
            ->route('admin.schedules.index')
            ->with('success', 'Session deleted.');
    }
}
