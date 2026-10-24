<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSkill;
use Illuminate\Http\Request;

/**
 * GET /api/matches — the Matches tab in Find.
 *
 * A member is suggested when they teach a skill you want to learn, or want to
 * learn a skill you teach. Both at once is a "perfect" match and sorts first.
 */
class MatchController extends Controller
{
    public function index(Request $request)
    {
        $me = $request->user();

        $mine = UserSkill::with('skill')->where('user_id', $me->id)->get();
        // Only skills you have earned a level in count as "I teach this".
        $iOffer = $mine->where('type', 'offered')->whereNotNull('level')->pluck('skill.title', 'skill_id');
        $iWant = $mine->where('type', 'wanted')->pluck('skill.title', 'skill_id');

        if ($iOffer->isEmpty() && $iWant->isEmpty()) {
            return response()->json(['matches' => []]);
        }

        $others = User::where('id', '!=', $me->id)
            ->where('status', 'active')
            ->where('role', '!=', 'admin')
            ->with('userSkills.skill')
            ->get();

        $matches = [];
        foreach ($others as $other) {
            $theyOffer = $other->userSkills->where('type', 'offered')->whereNotNull('level');
            $theyWant = $other->userSkills->where('type', 'wanted');

            // What they teach that I want / what they want that I teach.
            $teachesMe = $theyOffer->filter(fn ($s) => $iWant->has($s->skill_id))->first();
            $wantsMine = $theyWant->filter(fn ($s) => $iOffer->has($s->skill_id))->first();

            if (! $teachesMe && ! $wantsMine) {
                continue;
            }

            $matches[] = [
                'user_id' => $other->id,
                'name' => $other->name,
                'avatar_url' => $other->avatar_url,
                'presence' => $other->presence,
                'they_offer' => $teachesMe?->skill?->title ?? $theyOffer->first()?->skill?->title,
                'they_want' => $wantsMine?->skill?->title ?? $theyWant->first()?->skill?->title,
                'you_offer' => $wantsMine?->skill?->title ?? $iOffer->first(),
                'you_want' => $teachesMe?->skill?->title ?? $iWant->first(),
                'offers_what_you_want' => (bool) $teachesMe,
                'wants_what_you_offer' => (bool) $wantsMine,
                'perfect' => $teachesMe && $wantsMine,
            ];
        }

        usort($matches, fn ($a, $b) => [$b['perfect'], $a['name']] <=> [$a['perfect'], $b['name']]);

        return response()->json(['matches' => $matches]);
    }
}
