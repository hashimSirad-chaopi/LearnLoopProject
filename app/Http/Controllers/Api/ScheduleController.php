<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exchange;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller
{
    /** "Session Scheduling" — book a date/time for an ongoing exchange. */
    public function store(Request $request, Exchange $exchange)
    {
        $userId = $request->user()->id;
        abort_unless($exchange->provider_id === $userId || $exchange->learner_id === $userId, 403);

        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'time_start' => 'required',
            'time_end' => 'required',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $schedule = Schedule::create([
            'exchange_id' => $exchange->id,
            'date' => $request->date,
            'time_start' => $request->time_start,
            'time_end' => $request->time_end,
            'location' => $request->location,
            'notes' => $request->notes,
            'status' => 'pending',
        ]);

        // Scheduling a session moves the exchange from pending to ongoing.
        if ($exchange->status === 'pending') {
            $exchange->update(['status' => 'ongoing']);
        }

        return response()->json(['schedule' => $schedule], 201);
    }

    public function update(Request $request, Schedule $schedule)
    {
        $userId = $request->user()->id;
        $exchange = $schedule->exchange;
        abort_unless($exchange->provider_id === $userId || $exchange->learner_id === $userId, 403);

        $schedule->update($request->only('date', 'time_start', 'time_end', 'location', 'notes', 'status'));

        return response()->json(['schedule' => $schedule->fresh()]);
    }
}
