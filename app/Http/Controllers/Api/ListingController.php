<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exchange;
use App\Models\Listing;
use App\Models\Skill;
use App\Support\TeachingLevels;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ListingController extends Controller
{
    /**
     * Matches the Flutter Feed screen. Only 'active' listings are shown
     * (they go live as soon as they are posted; an admin only monitors them),
     * except a member always sees their own, so they can reopen a closed one
     * or read why an admin disabled one.
     * Supports ?q= for the Feed's search bar.
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $q = trim((string) $request->get('q', ''));

        $listings = Listing::with(['user', 'category', 'offersSkill', 'wantsSkill'])
            ->withCount(['likes', 'comments'])
            ->withExists(['likes as liked_by_me' => fn ($q) => $q->where('user_id', $userId)])
            ->where(function ($query) use ($userId) {
                $query->where('status', 'active')->orWhere('user_id', $userId);
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('title', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%")
                        ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$q}%"));
                });
            })
            ->latest()
            ->get();

        return response()->json(['listings' => $listings]);
    }

    /** "Skill Listing/Posting" — the Create Post screen's + button. */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            // The app posts the skill you WANT and the skill you OFFER. Leave offers out for a
            // learner-only listing ("I want to learn X", nothing to teach yet). The category is
            // taken from the skill (Listing::booted), so category_id is only a fallback.
            // Swap: both. Learn only: wants only. Teach only: offers only (nothing is asked back).
            'wants_skill_id' => 'nullable|required_without:offers_skill_id|exists:skills,id',
            'offers_skill_id' => 'nullable|required_without:wants_skill_id|exists:skills,id|different:wants_skill_id',
            'offers_level' => 'nullable|in:easy,moderate,hard,expert,professional',
            'wants_level' => 'nullable|in:easy,moderate,hard,expert,professional',
            'budget' => 'nullable|integer|min:0|max:1000000',
            'category_id' => 'nullable|exists:categories,id',
        ], [
            'wants_skill_id.required_without' => 'Choose the skill you want to learn or the skill you teach.',
            'offers_skill_id.required_without' => 'Choose the skill you teach or the skill you want to learn.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        // You can only offer to teach a skill at a level you have EARNED.
        $offersLevel = null;
        if ($request->offers_skill_id) {
            [$offersLevel, $problem] = $this->checkTeachable($request->user()->id, (int) $request->offers_skill_id, $request->offers_level);
            if ($problem) {
                return response()->json(['message' => $problem, 'errors' => ['offers_skill_id' => [$problem]]], 403);
            }
        }

        $listing = Listing::create([
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'wants_skill_id' => $request->wants_skill_id,
            'offers_skill_id' => $request->offers_skill_id,
            'offers_level' => $offersLevel,
            // Teach only asks nothing back: no wanted skill, level or budget.
            'wants_level' => $request->wants_skill_id ? $request->wants_level : null,
            'budget' => ($request->wants_skill_id && $request->wants_level === 'professional') ? $request->budget : null,
            'category_id' => $request->category_id,
            'description' => $request->description,
            'status' => 'active', // live straight away; an admin monitors and can disable it with a reason
        ]);

        return response()->json(['listing' => $listing->load('user', 'category', 'offersSkill', 'wantsSkill')], 201);
    }

    public function show(Request $request, Listing $listing)
    {
        if ($listing->status !== 'active' && $listing->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Listing not found'], 404);
        }

        $listing->loadCount(['likes', 'comments'])
            ->loadExists(['likes as liked_by_me' => fn ($q) => $q->where('user_id', $request->user()->id)]);

        return response()->json(['listing' => $listing->load('user', 'category', 'offersSkill', 'wantsSkill')]);
    }

    public function update(Request $request, Listing $listing)
    {
        if ($listing->user_id !== $request->user()->id) {
            return response()->json(['message' => 'You can only edit your own listings'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'category_id' => 'sometimes|nullable|exists:categories,id',
            'wants_skill_id' => 'sometimes|nullable|exists:skills,id',
            'offers_skill_id' => 'sometimes|nullable|exists:skills,id',
            'offers_level' => 'sometimes|nullable|in:easy,moderate,hard,expert,professional',
            'wants_level' => 'sometimes|nullable|in:easy,moderate,hard,expert,professional',
            'budget' => 'sometimes|nullable|integer|min:0|max:1000000',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $listing->fill($request->only('title', 'description', 'category_id', 'wants_skill_id', 'offers_skill_id', 'offers_level', 'wants_level', 'budget'));

        // A listing needs at least one skill: the one you teach, the one you want, or both.
        if (! $listing->wants_skill_id && ! $listing->offers_skill_id) {
            return response()->json([
                'message' => 'Choose the skill you teach or the skill you want to learn.',
                'errors' => ['offers_skill_id' => ['Choose the skill you teach or the skill you want to learn.']],
            ], 422);
        }
        if ($listing->wants_skill_id && $listing->wants_skill_id === $listing->offers_skill_id) {
            return response()->json(['message' => 'Pick a different skill than the one you teach', 'errors' => ['wants_skill_id' => ['Pick a different skill than the one you teach']]], 422);
        }
        // Teach only: nothing is asked back, so no level or budget either.
        if (! $listing->wants_skill_id) {
            $listing->wants_level = null;
        }

        // Same rule as when posting: the skill you offer must be at a level you have earned.
        if ($listing->offers_skill_id) {
            [$offersLevel, $problem] = $this->checkTeachable($request->user()->id, (int) $listing->offers_skill_id, $listing->offers_level);
            if ($problem) {
                return response()->json(['message' => $problem, 'errors' => ['offers_skill_id' => [$problem]]], 403);
            }
            $listing->offers_level = $offersLevel;
        } else {
            $listing->offers_level = null;
        }
        // Only a Professional (paid) listing carries a budget.
        if ($listing->wants_level !== 'professional') {
            $listing->budget = null;
        }

        // Editing never changes whether it is live: a disabled listing stays disabled until an admin
        // restores it, and a closed one stays closed until its owner reopens it.
        $listing->save();

        return response()->json(['listing' => $listing->fresh(['user', 'category', 'offersSkill', 'wantsSkill'])]);
    }

    /** "Close" in My Listings: the member takes their own listing out of the feed (status inactive). */
    public function close(Request $request, Listing $listing)
    {
        if ($listing->user_id !== $request->user()->id) {
            return response()->json(['message' => 'You can only close your own listings'], 403);
        }

        if ($listing->isDisabledByAdmin()) {
            return response()->json(['message' => 'An admin disabled this listing. You can edit or delete it.'], 422);
        }

        $listing->update(['status' => 'inactive']);

        return response()->json(['listing' => $listing->fresh(['user', 'category', 'offersSkill', 'wantsSkill'])]);
    }

    /** "Reopen": a listing the member closed goes live again. One an admin disabled cannot be reopened here. */
    public function reopen(Request $request, Listing $listing)
    {
        if ($listing->user_id !== $request->user()->id) {
            return response()->json(['message' => 'You can only reopen your own listings'], 403);
        }

        if ($listing->isDisabledByAdmin()) {
            return response()->json(['message' => 'An admin disabled this listing, so it cannot be reopened. See the reason in My Listings.'], 422);
        }
        if ($listing->status !== 'inactive') {
            return response()->json(['message' => 'Only a closed listing can be reopened'], 422);
        }

        $listing->update(['status' => 'active']);

        return response()->json(['listing' => $listing->fresh(['user', 'category', 'offersSkill', 'wantsSkill'])]);
    }

    public function destroy(Request $request, Listing $listing)
    {
        if ($listing->user_id !== $request->user()->id) {
            return response()->json(['message' => 'You can only delete your own listings'], 403);
        }

        $listing->delete();

        return response()->json(['message' => 'Listing deleted']);
    }

    /**
     * "Exchange Proposal" — the Feed's "Propose Exchange" button.
     * Creates a real Exchange row. provider = who TEACHES `skill_offered`,
     * learner = who learns it, `skill_wanted` = what the learner teaches back.
     *
     *  - Two-way listing: the owner is the provider, the caller is the learner
     *    and sends `skill_wanted` (what the caller will teach the owner).
     *  - Learner-only listing ("I want to learn X"): the CALLER becomes the
     *    provider and teaches X; the owner is the learner and nothing is
     *    swapped back (skill_wanted stays NULL). Only a member who has passed
     *    every lesson of X can answer it, as in the app.
     * `requested_by` is the caller, so the OTHER member is the one who accepts.
     */
    public function propose(Request $request, Listing $listing)
    {
        $learnerOnly = $listing->isLearnerOnly();
        $teachOnly = $listing->isTeachOnly();

        $validator = Validator::make($request->all(), [
            'skill_wanted' => (($learnerOnly || $teachOnly) ? 'nullable' : 'required').'|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        if ($listing->user_id === $request->user()->id) {
            return response()->json(['message' => 'You cannot propose an exchange on your own listing'], 422);
        }

        if ($listing->status !== 'active') {
            return response()->json(['message' => 'This listing is not open'], 422);
        }

        $me = $request->user()->id;
        $needed = $listing->wants_level ?: 'easy'; // the level the poster wants to learn at

        // One open request per pair: tapping the button again (or twice quickly) must not create a second one.
        // A cancelled / declined or completed exchange does not count, so you can offer again after those.
        $openBetween = fn ($provider, $learner) => Exchange::whereIn('status', ['pending', 'ongoing', 'disputed'])
            ->where('provider_id', $provider)->where('learner_id', $learner)
            ->where(fn ($q) => $learnerOnly
                ? $q->whereNull('skill_wanted')->where('skill_offered', $listing->wantsSkill?->title)
                : ($teachOnly
                    ? $q->whereNull('skill_wanted')->where('skill_offered', $listing->offersSkill?->title)
                    : $q->where('skill_offered', $listing->offersSkill?->title ?? $listing->title)->whereRaw('lower(skill_wanted) = ?', [mb_strtolower(trim((string) $request->skill_wanted))])))
            ->exists();
        $alreadyOpen = $learnerOnly ? $openBetween($me, $listing->user_id) : $openBetween($listing->user_id, $me);
        if ($alreadyOpen) {
            return response()->json(['message' => 'You already have a pending or active exchange with this user for this listing. Wait for their answer, or finish it first.'], 409);
        }

        if ($learnerOnly) {
            $skill = $listing->wantsSkill;
            if (! TeachingLevels::canTeach($me, $skill, $needed)) {
                return response()->json(['message' => $this->earnMessage($skill->title, $needed)], 403);
            }

            $exchange = Exchange::create([
                'provider_id' => $request->user()->id,
                'learner_id' => $listing->user_id,
                'requested_by' => $request->user()->id,
                'skill_offered' => $skill->title,
                'skill_wanted' => null,
                'status' => 'pending',
                // A paid listing: the caller is the professional and confirms when it is finished.
                'is_professional' => $listing->wants_level === 'professional',
            ]);
        } elseif ($teachOnly) {
            // Teach only ("I like to teach"): the POSTER teaches, the CALLER is the learner and
            // nothing is swapped back. The poster must still be able to teach it.
            if ($listing->offers_skill_id && ! TeachingLevels::canTeach($listing->user_id, (int) $listing->offers_skill_id, $listing->offers_level)) {
                return response()->json(['message' => 'This user cannot teach that skill right now.'], 422);
            }

            $exchange = Exchange::create([
                'provider_id' => $listing->user_id,
                'learner_id' => $request->user()->id,
                'requested_by' => $request->user()->id,
                'skill_offered' => $listing->offersSkill->title,
                'skill_wanted' => null,
                'status' => 'pending',
            ]);
        } else {
            // The learner teaches `skill_wanted` back, so they must have earned it too.
            $back = Skill::whereRaw('lower(title) = ?', [mb_strtolower(trim((string) $request->skill_wanted))])->first();
            if (! $back) {
                return response()->json(['message' => 'Choose a skill from the LearnLoop catalog to teach back.'], 422);
            }
            $backLevel = $listing->wants_skill_id === $back->id ? $needed : 'easy';
            if (! TeachingLevels::canTeach($me, $back, $backLevel)) {
                return response()->json(['message' => $this->earnMessage($back->title, $backLevel)], 403);
            }
            // ...and the poster must still be able to teach what they offer.
            if ($listing->offers_skill_id && ! TeachingLevels::canTeach($listing->user_id, (int) $listing->offers_skill_id, $listing->offers_level)) {
                return response()->json(['message' => 'This user cannot teach that skill right now.'], 422);
            }

            $exchange = Exchange::create([
                'provider_id' => $listing->user_id,
                'learner_id' => $request->user()->id,
                'requested_by' => $request->user()->id,
                'skill_offered' => $listing->offersSkill?->title ?? $listing->title,
                'skill_wanted' => $back->title,
                'status' => 'pending',
            ]);
        }

        return response()->json(['exchange' => $exchange->load('provider', 'learner')], 201);
    }

    /**
     * May this member offer to teach `$skillId` at `$asked` (default: the highest they earned)?
     *
     * @return array{0:?string,1:?string} [level to store, error message or null]
     */
    private function checkTeachable(int $userId, int $skillId, ?string $asked): array
    {
        $earned = TeachingLevels::earned($userId, $skillId);
        $title = Skill::whereKey($skillId)->value('title');

        $experience = (bool) Skill::whereKey($skillId)->value('experience_based');

        if ($earned === null) {
            return [null, $experience
                ? "{$title} can only be taught as a verified Professional. Send your certification in Learn > Continue Learning and wait for an admin to approve it."
                : "Finish the Easy lessons of {$title} before you offer to teach it. Open Learn > Continue Learning."];
        }
        // An experience skill is only ever offered at the Professional level.
        $level = $experience ? 'professional' : ($asked ?: $earned);
        if (! TeachingLevels::covers($earned, $level)) {
            return [null, $this->earnMessage($title, $level, 'offer')];
        }

        return [$level, null];
    }

    private function earnMessage(string $title, string $level, string $verb = 'teach'): string
    {
        return $level === 'professional'
            ? "You must be a verified Professional in {$title} to {$verb} it at that level. Send your proof and pass the hard quiz first."
            : "You have not earned ".ucfirst($level)." in {$title} yet, so you cannot {$verb} it at that level. Finish its lessons in Learn > Continue Learning.";
    }
}
