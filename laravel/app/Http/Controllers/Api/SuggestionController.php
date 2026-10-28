<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\ListingComment;
use App\Models\ListingLike;
use App\Models\UserBlock;
use App\Models\UserSkill;
use Illuminate\Http\Request;

/**
 * GET /api/suggestions — listings picked for this member from what they like.
 *
 * Interests are collected from
 *   - listings they liked          (their skills and category)   strongest signal
 *   - listings they commented on   (their skills)
 *   - skills on their profile they want to learn
 * and every live listing from someone else is scored against them. A listing that WANTS a
 * skill this member teaches also scores (a two-way swap is possible).
 * Listings they already liked, their own, blocked members' and disabled ones are left out.
 */
class SuggestionController extends Controller
{
    private const LIMIT = 20;

    public function index(Request $request)
    {
        $me = $request->user();

        $liked = ListingLike::where('user_id', $me->id)->latest()->limit(60)->pluck('listing_id');
        $commented = ListingComment::where('user_id', $me->id)->latest()->limit(60)->pluck('listing_id');

        $source = Listing::with(['offersSkill', 'wantsSkill', 'category'])
            ->whereIn('id', $liked->merge($commented)->unique())
            ->get()
            ->keyBy('id');

        // skill_id => [weight, reason]; the first (strongest) reason for a skill wins.
        $interest = [];
        $categories = [];
        $addSkill = function (?int $skillId, int $weight, string $reason) use (&$interest) {
            if (! $skillId) {
                return;
            }
            if (! isset($interest[$skillId])) {
                $interest[$skillId] = ['weight' => 0, 'reason' => $reason];
            }
            $interest[$skillId]['weight'] += $weight;
        };

        foreach ($liked as $listingId) {
            $l = $source->get($listingId);
            if (! $l) {
                continue;
            }
            $label = 'you liked "'.\Illuminate\Support\Str::limit($l->title, 40).'"';
            // The skill that listing teaches, and the skill it asks for, are both things this member is drawn to.
            $addSkill($l->offers_skill_id, 3, $label);
            $addSkill($l->wants_skill_id, 2, $label);
            if ($l->category_id) {
                $categories[$l->category_id] = ($categories[$l->category_id] ?? ['weight' => 0, 'name' => $l->category?->name]);
                $categories[$l->category_id]['weight'] += 1;
            }
        }
        foreach ($commented as $listingId) {
            $l = $source->get($listingId);
            if ($l) {
                $label = 'you commented on "'.\Illuminate\Support\Str::limit($l->title, 40).'"';
                $addSkill($l->offers_skill_id, 2, $label);
            }
        }

        $mine = UserSkill::where('user_id', $me->id)->get();
        foreach ($mine->where('type', 'wanted') as $row) {
            $addSkill($row->skill_id, 4, 'is on your "want to learn" list');
        }
        $iTeach = $mine->where('type', 'offered')->whereNotNull('level')->pluck('skill_id')->all();

        if (empty($interest) && empty($categories) && empty($iTeach)) {
            return response()->json(['suggestions' => [], 'has_signals' => false]);
        }

        $blocked = UserBlock::where('blocker_id', $me->id)->pluck('blocked_id')
            ->merge(UserBlock::where('blocked_id', $me->id)->pluck('blocker_id'))->all();

        $candidates = Listing::with(['user', 'category', 'offersSkill', 'wantsSkill'])
            ->withCount(['likes', 'comments'])
            ->withExists(['likes as liked_by_me' => fn ($q) => $q->where('user_id', $me->id)])
            ->where('status', 'active')
            ->where('user_id', '!=', $me->id)
            ->whereNotIn('id', $liked)
            ->when($blocked, fn ($q) => $q->whereNotIn('user_id', $blocked))
            ->whereHas('user', fn ($q) => $q->where('status', 'active'))
            ->latest()
            ->limit(300)
            ->get();

        $scored = [];
        foreach ($candidates as $c) {
            $score = 0;
            $reason = null;

            // They teach something this member likes / wants.
            if ($c->offers_skill_id && isset($interest[$c->offers_skill_id])) {
                $hit = $interest[$c->offers_skill_id];
                $score += $hit['weight'] * 2;
                $reason = 'Teaches '.$c->offersSkill?->title.' — '.$hit['reason'];
            }
            // They are looking for something this member can teach.
            if ($c->wants_skill_id && in_array($c->wants_skill_id, $iTeach, true)) {
                $score += 5;
                $reason ??= 'Wants to learn '.$c->wantsSkill?->title.', which you teach';
            }
            // Same category as listings they liked.
            if ($c->category_id && isset($categories[$c->category_id])) {
                $score += $categories[$c->category_id]['weight'];
                $reason ??= 'In '.$categories[$c->category_id]['name'].', a category you like';
            }

            if ($score > 0) {
                $c->setAttribute('reason', $reason);
                $c->setAttribute('score', $score);
                $scored[] = $c;
            }
        }

        usort($scored, fn ($a, $b) => [$b->score, $b->created_at] <=> [$a->score, $a->created_at]);

        return response()->json([
            'suggestions' => array_slice($scored, 0, self::LIMIT),
            'has_signals' => true,
        ]);
    }
}
