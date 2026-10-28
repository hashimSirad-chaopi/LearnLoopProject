<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exchange;
use App\Models\Notification;
use App\Models\Skill;
use App\Support\TeachingLevels;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ExchangeController extends Controller
{
    /**
     * Matches the Flutter Exchange screen's two tabs:
     * - Requests tab:      GET /api/exchanges?role=provider&status=pending
     * - My Exchanges tab:  GET /api/exchanges?status=ongoing,completed
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $query = Exchange::with(['provider', 'learner', 'schedules', 'ratings'])
            ->where(function ($q) use ($userId) {
                $q->where('provider_id', $userId)->orWhere('learner_id', $userId);
            });

        if ($request->filled('role') && $request->role === 'provider') {
            $query->where('provider_id', $userId);
        } elseif ($request->filled('role') && $request->role === 'learner') {
            $query->where('learner_id', $userId);
        }

        if ($request->filled('status')) {
            $statuses = explode(',', $request->status);
            $query->whereIn('status', $statuses);
        }

        // Exchanges list: Show = All | In Progress | All Done.
        $query->when($request->query('progress') === 'in_progress', fn ($q) => $q->whereIn('status', ['ongoing', 'disputed']))
            ->when($request->query('progress') === 'done', fn ($q) => $q->where('status', 'completed'));

        // Sort = Newest (default) | Oldest.
        $request->query('sort') === 'oldest' ? $query->oldest() : $query->latest();

        return response()->json(['exchanges' => $query->get()]);
    }

    public function show(Request $request, Exchange $exchange)
    {
        $this->authorizeParty($request, $exchange);

        return response()->json(['exchange' => $exchange->load(['provider', 'learner', 'schedules', 'ratings'])]);
    }

    /** Only the member who RECEIVED the request (not the one who sent it) can accept it. */
    public function accept(Request $request, Exchange $exchange)
    {
        if ($exchange->responderId() !== $request->user()->id) {
            return response()->json(['message' => 'Only the user who received this request can accept it'], 403);
        }

        if ($exchange->status !== 'pending') {
            return response()->json(['message' => 'Only pending requests can be accepted'], 422);
        }

        $exchange->update(['status' => 'ongoing']);

        return response()->json(['exchange' => $exchange->fresh(['provider', 'learner'])]);
    }

    public function decline(Request $request, Exchange $exchange)
    {
        if ($exchange->responderId() !== $request->user()->id) {
            return response()->json(['message' => 'Only the user who received this request can decline it'], 403);
        }

        if ($exchange->status !== 'pending') {
            return response()->json(['message' => 'Only pending requests can be declined'], 422);
        }

        $exchange->update(['status' => 'cancelled']);

        return response()->json(['exchange' => $exchange->fresh(['provider', 'learner'])]);
    }

    /**
     * "Exchange Negotiation" — either party proposes different terms.
     * Kept simple: updates the requested skill and/or leaves a note,
     * without changing status away from pending.
     */
    public function negotiate(Request $request, Exchange $exchange)
    {
        $this->authorizeParty($request, $exchange);

        // Terms can only change while the request is still waiting for an answer.
        // (The app sends a counter-offer as a chat message instead.)
        if ($exchange->status !== 'pending') {
            return response()->json(['message' => 'Only a pending request can be renegotiated'], 422);
        }

        $validator = Validator::make($request->all(), [
            'skill_wanted' => 'sometimes|string|max:255',
            'skill_offered' => 'sometimes|string|max:255',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        // Changing the terms must not be a way around "earn it first": whoever teaches a skill
        // must have earned at least its Easy level.
        foreach ([['skill_offered', $exchange->provider_id], ['skill_wanted', $exchange->learner_id]] as [$field, $teacherId]) {
            if ($request->filled($field) && $request->input($field) !== $exchange->{$field}) {
                $skill = Skill::whereRaw('lower(title) = ?', [mb_strtolower(trim($request->input($field)))])->first();
                if (! $skill || ! TeachingLevels::canTeach($teacherId, $skill, 'easy')) {
                    return response()->json(['message' => 'The user who teaches '.$request->input($field).' has not earned a level in it yet.'], 422);
                }
            }
        }

        $exchange->update([
            'skill_wanted' => $request->skill_wanted ?? $exchange->skill_wanted,
            'skill_offered' => $request->skill_offered ?? $exchange->skill_offered,
            'admin_notes' => $request->note ?? $exchange->admin_notes,
        ]);

        return response()->json(['exchange' => $exchange->fresh(['provider', 'learner'])]);
    }

    /**
     * "Exchange Completion & Status Tracking".
     *
     *  - Paid exchange with a professional: ONLY the professional (the provider) can confirm it is
     *    finished. The learner can rate once it is completed.
     *  - Member <-> member: nobody can finish alone. Use finish-request + finish-approve, which
     *    completes it once BOTH sides agree.
     */
    public function complete(Request $request, Exchange $exchange)
    {
        $this->authorizeParty($request, $exchange);

        if ($exchange->status !== 'ongoing') {
            return response()->json(['message' => 'Only ongoing exchanges can be completed'], 422);
        }

        if (! $exchange->is_professional) {
            return response()->json(['message' => 'Both members have to agree to finish. Tap "Ask to finish" and wait for your partner to agree.'], 422);
        }

        if ($exchange->provider_id !== $request->user()->id) {
            return response()->json(['message' => 'Only the professional can confirm this exchange is finished.'], 403);
        }

        $exchange->update(['status' => 'completed'] + $exchange->clearFinishRequest());

        Notification::notifyFromMember(
            $exchange->learner_id,
            'finish_approved',
            'Exchange finished',
            "{$request->user()->name} confirmed your {$exchange->skillPair()} exchange is finished. You can rate them now.",
            'exchange',
            $exchange->id
        );

        return response()->json(['exchange' => $exchange->fresh(['provider', 'learner'])]);
    }

    /**
     * "Finish exchange" step 1 (member <-> member): a member asks to finish. Nothing is finished
     * yet; the other member is notified and has to agree or say "not yet".
     */
    public function requestFinish(Request $request, Exchange $exchange)
    {
        $this->authorizeParty($request, $exchange);

        if ($exchange->status !== 'ongoing') {
            return response()->json(['message' => 'Only an ongoing exchange can be finished'], 422);
        }

        if ($exchange->is_professional) {
            return response()->json(['message' => 'In a paid exchange the professional confirms when it is finished.'], 422);
        }

        $me = $request->user();

        if ($exchange->hasOpenFinishRequest()) {
            return response()->json([
                'message' => $exchange->finish_requested_by === $me->id
                    ? 'You already asked to finish. Waiting for your partner to agree.'
                    : 'Your partner already asked to finish. Open the exchange and agree.',
            ], 422);
        }

        $exchange->update([
            'finish_requested_by' => $me->id,
            'finish_requested_at' => now(),
            'finish_declined_at' => null,
            'finish_decline_note' => null,
        ]);

        Notification::notifyFromMember(
            $this->partnerId($exchange, $me->id),
            'finish_requested',
            'Finish exchange request',
            "{$me->name} wants to finish your {$exchange->skillPair()} exchange. Open the exchange to agree or say not yet.",
            'exchange',
            $exchange->id
        );

        return response()->json(['exchange' => $exchange->fresh(['provider', 'learner'])]);
    }

    /** Step 2a: the OTHER member agrees. The exchange is completed for both. */
    public function approveFinish(Request $request, Exchange $exchange)
    {
        $me = $this->openFinishFor($request, $exchange);
        if ($me instanceof \Illuminate\Http\JsonResponse) {
            return $me;
        }

        $askedBy = $exchange->finish_requested_by;
        $exchange->update(['status' => 'completed'] + $exchange->clearFinishRequest());

        Notification::notifyFromMember(
            $askedBy,
            'finish_approved',
            'Exchange finished',
            "{$me->name} agreed. Your {$exchange->skillPair()} exchange is finished. You can rate each other now.",
            'exchange',
            $exchange->id
        );

        return response()->json(['exchange' => $exchange->fresh(['provider', 'learner'])]);
    }

    /** Step 2b: the OTHER member says "not yet". The exchange keeps going. */
    public function declineFinish(Request $request, Exchange $exchange)
    {
        $me = $this->openFinishFor($request, $exchange);
        if ($me instanceof \Illuminate\Http\JsonResponse) {
            return $me;
        }

        $note = trim((string) $request->input('note', ''));
        $exchange->update([
            'finish_declined_at' => now(),
            'finish_decline_note' => $note !== '' ? mb_substr($note, 0, 2000) : null,
        ]);

        Notification::notifyFromMember(
            $exchange->finish_requested_by,
            'finish_declined',
            'Not ready to finish yet',
            "{$me->name} is not ready to finish your {$exchange->skillPair()} exchange yet.".($note !== '' ? ' They said: '.Str::limit($note, 140) : ''),
            'exchange',
            $exchange->id
        );

        return response()->json(['exchange' => $exchange->fresh(['provider', 'learner'])]);
    }

    /** The member who asked takes the request back (also clears a "not yet" answer). */
    public function withdrawFinish(Request $request, Exchange $exchange)
    {
        $this->authorizeParty($request, $exchange);

        if ($exchange->finish_requested_by !== $request->user()->id || $exchange->status !== 'ongoing') {
            return response()->json(['message' => 'There is no finish request of yours to withdraw'], 422);
        }

        $open = $exchange->hasOpenFinishRequest();
        $exchange->update($exchange->clearFinishRequest());

        if ($open) {
            Notification::notifyFromMember(
                $this->partnerId($exchange, $request->user()->id),
                'finish_withdrawn',
                'Finish request withdrawn',
                "{$request->user()->name} withdrew the request to finish your {$exchange->skillPair()} exchange. It continues.",
                'exchange',
                $exchange->id
            );
        }

        return response()->json(['exchange' => $exchange->fresh(['provider', 'learner'])]);
    }

    /** The member who must answer an open finish request, or the error to send back. */
    private function openFinishFor(Request $request, Exchange $exchange)
    {
        $this->authorizeParty($request, $exchange);

        if ($exchange->status !== 'ongoing' || ! $exchange->hasOpenFinishRequest()) {
            return response()->json(['message' => 'There is no finish request waiting for your answer'], 422);
        }

        if ($exchange->finish_requested_by === $request->user()->id) {
            return response()->json(['message' => 'Your partner has to answer your finish request'], 403);
        }

        return $request->user();
    }

    /**
     * "File a Dispute" (dispute_screen.dart). Marks the exchange 'disputed' so it
     * shows up first in the admin's Exchange Monitoring, with the reason the
     * member typed ("No-show — details..."). An admin resolves it there and both
     * members are notified.
     */
    public function dispute(Request $request, Exchange $exchange)
    {
        $this->authorizeParty($request, $exchange);

        if (! in_array($exchange->status, ['ongoing', 'completed'], true)) {
            return response()->json(['message' => 'Only an ongoing or completed exchange can be disputed'], 422);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $exchange->update([
            'status' => 'disputed',
            'dispute_reason' => $request->reason,
            'disputed_by' => $request->user()->id,
            'disputed_at' => now(),
            'resolved_at' => null,
        ]);

        return response()->json(['exchange' => $exchange->fresh(['provider', 'learner'])]);
    }

    /**
     * "Cancel exchange" step 1: a member asks to cancel an ongoing exchange and says why.
     * Nothing is cancelled yet; the other member is notified and has to accept or decline.
     */
    public function requestCancel(Request $request, Exchange $exchange)
    {
        $this->authorizeParty($request, $exchange);

        if ($exchange->status !== 'ongoing') {
            return response()->json(['message' => 'Only an ongoing exchange can be cancelled'], 422);
        }

        if ($exchange->hasOpenCancelRequest()) {
            return response()->json(['message' => 'A cancel request is already waiting for an answer'], 422);
        }

        $validator = Validator::make($request->all(), ['reason' => 'required|string|max:2000']);
        if ($validator->fails()) {
            return response()->json(['message' => 'Please say why you want to cancel', 'errors' => $validator->errors()], 422);
        }

        $me = $request->user();
        $exchange->update([
            'cancel_requested_by' => $me->id,
            'cancel_reason' => trim($request->reason),
            'cancel_requested_at' => now(),
            'cancel_declined_at' => null,
            'cancel_decline_note' => null,
        ]);

        Notification::notifyFromMember(
            $this->partnerId($exchange, $me->id),
            'cancel_requested',
            'Cancel exchange request',
            "{$me->name} wants to cancel your {$exchange->skillPair()} exchange. Reason: ".Str::limit(trim($request->reason), 140).' Open the exchange to accept or decline.',
            'exchange',
            $exchange->id
        );

        return response()->json(['exchange' => $exchange->fresh(['provider', 'learner'])]);
    }

    /** Step 2a: the OTHER member agrees, so the exchange is cancelled for both. */
    public function acceptCancel(Request $request, Exchange $exchange)
    {
        $me = $this->openCancelFor($request, $exchange);
        if ($me instanceof \Illuminate\Http\JsonResponse) {
            return $me;
        }

        $exchange->update(['status' => 'cancelled']);

        Notification::notifyFromMember(
            $exchange->cancel_requested_by,
            'cancel_accepted',
            'Exchange cancelled',
            "{$me->name} accepted. Your {$exchange->skillPair()} exchange is cancelled.",
            'exchange',
            $exchange->id
        );

        return response()->json(['exchange' => $exchange->fresh(['provider', 'learner'])]);
    }

    /** Step 2b: the OTHER member declines. The exchange keeps going; the requester may report it to the admin. */
    public function declineCancel(Request $request, Exchange $exchange)
    {
        $me = $this->openCancelFor($request, $exchange);
        if ($me instanceof \Illuminate\Http\JsonResponse) {
            return $me;
        }

        $note = trim((string) $request->input('note', ''));
        $exchange->update(['cancel_declined_at' => now(), 'cancel_decline_note' => $note !== '' ? mb_substr($note, 0, 2000) : null]);

        Notification::notifyFromMember(
            $exchange->cancel_requested_by,
            'cancel_declined',
            'Cancel request declined',
            "{$me->name} did not agree to cancel your {$exchange->skillPair()} exchange.".($note !== '' ? ' They said: '.Str::limit($note, 140) : '').' If you cannot agree, you can report it to the admin from the exchange menu.',
            'exchange',
            $exchange->id
        );

        return response()->json(['exchange' => $exchange->fresh(['provider', 'learner'])]);
    }

    /** The member who asked takes the request back. */
    public function withdrawCancel(Request $request, Exchange $exchange)
    {
        $this->authorizeParty($request, $exchange);

        if ($exchange->cancel_requested_by !== $request->user()->id || $exchange->status !== 'ongoing') {
            return response()->json(['message' => 'There is no cancel request of yours to withdraw'], 422);
        }

        $open = $exchange->hasOpenCancelRequest();
        $exchange->update([
            'cancel_requested_by' => null,
            'cancel_reason' => null,
            'cancel_requested_at' => null,
            'cancel_declined_at' => null,
            'cancel_decline_note' => null,
        ]);

        if ($open) {
            Notification::notifyFromMember(
                $this->partnerId($exchange, $request->user()->id),
                'cancel_withdrawn',
                'Cancel request withdrawn',
                "{$request->user()->name} withdrew the request to cancel your {$exchange->skillPair()} exchange. It continues.",
                'exchange',
                $exchange->id
            );
        }

        return response()->json(['exchange' => $exchange->fresh(['provider', 'learner'])]);
    }

    /** The member who must answer an open cancel request, or the error to send back. */
    private function openCancelFor(Request $request, Exchange $exchange)
    {
        $this->authorizeParty($request, $exchange);

        if ($exchange->status !== 'ongoing' || ! $exchange->hasOpenCancelRequest()) {
            return response()->json(['message' => 'There is no cancel request waiting for an answer'], 422);
        }

        if ($exchange->cancel_requested_by === $request->user()->id) {
            return response()->json(['message' => 'The other user has to answer your cancel request'], 403);
        }

        return $request->user();
    }

    private function partnerId(Exchange $exchange, int $myId): int
    {
        return $exchange->provider_id === $myId ? $exchange->learner_id : $exchange->provider_id;
    }

    private function authorizeParty(Request $request, Exchange $exchange): void
    {
        $userId = $request->user()->id;

        abort_unless(
            $exchange->provider_id === $userId || $exchange->learner_id === $userId,
            403,
            'You are not part of this exchange'
        );
    }
}
