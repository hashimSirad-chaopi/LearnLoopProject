<?php

namespace App\Http\Controllers;

use App\Models\Exchange;
use Illuminate\Http\Request;

class ExchangeController extends Controller
{
    /** Statuses an exchange can be in. */
    public const STATUSES = ['pending', 'ongoing', 'completed', 'disputed', 'cancelled', 'resolved'];

    /**
     * List all exchanges, optionally filtered by status.
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'all');

        $query = Exchange::with(['provider', 'learner'])
            ->withCount(['schedules', 'messages', 'ratings']);

        if ($status !== 'all' && in_array($status, self::STATUSES, true)) {
            $query->where('status', $status);
        }

        return view('admin.exchanges.index', [
            'exchanges'     => $query->latest()->paginate(15)->withQueryString(),
            'currentStatus' => $status,
            'statusOptions' => array_merge(['all'], self::STATUSES),
            'counts'        => Exchange::selectRaw('status, count(*) as total')
                                  ->groupBy('status')
                                  ->pluck('total', 'status'),
        ]);
    }

    /**
     * Show one exchange with its schedules, messages and ratings.
     */
    public function show(Exchange $exchange)
    {
        $exchange->load([
            'provider',
            'learner',
            'schedules' => fn ($q) => $q->orderBy('date'),
            'messages'  => fn ($q) => $q->with(['sender', 'recipient'])->latest(),
            'ratings'   => fn ($q) => $q->with(['rater', 'ratedUser', 'attachments']),
        ]);

        return view('admin.exchanges.show', [
            'exchange'      => $exchange,
            'statusOptions' => self::STATUSES,
        ]);
    }

    /**
     * Change an exchange's status.
     */
    public function updateStatus(Request $request, Exchange $exchange)
    {
        $validated = $request->validate([
            'status'      => 'required|in:' . implode(',', self::STATUSES),
            'admin_notes' => 'nullable|string|max:500',
        ]);

        $exchange->status = $validated['status'];

        if (! empty($validated['admin_notes'])) {
            $exchange->admin_notes = $validated['admin_notes'];
        }

        $exchange->save();

        return back()->with('success', 'Exchange status updated to ' . $validated['status'] . '.');
    }

    /**
     * Cancel an exchange.
     */
    public function cancel(Exchange $exchange)
    {
        $exchange->update(['status' => 'cancelled']);

        return redirect()
            ->route('admin.exchanges.index')
            ->with('success', 'Exchange #' . $exchange->id . ' has been cancelled.');
    }

    /**
     * Record the outcome of a disputed exchange.
     */
    public function resolveDispute(Request $request, Exchange $exchange)
    {
        $validated = $request->validate([
            'resolution' => 'required|in:provider_favoured,learner_favoured,mutual_cancellation',
            'notes'      => 'required|string|max:500',
        ]);

        $exchange->update([
            'status'      => 'resolved',
            'resolution'  => $validated['resolution'],
            'admin_notes' => $validated['notes'],
        ]);

        return back()->with('success', 'Dispute resolved.');
    }
}
