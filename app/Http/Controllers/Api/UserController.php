<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exchange;
use App\Models\Lesson;
use App\Models\User;
use App\Models\UserBlock;
use App\Support\AuthorTrust;
use App\Support\TeachingLevels;
use Illuminate\Http\Request;

/** Another member's public profile (the app's UserProfileScreen). No e-mail or private data. */
class UserController extends Controller
{
    /** GET /api/users/{user} */
    public function show(Request $request, User $user)
    {
        if ($user->status !== 'active') {
            return response()->json(['message' => 'User not found'], 404);
        }

        // "Active now" / "Active 5 min ago" on the profile: only for a member you are (or were)
        // exchanging with, as learner or coach, and never across a block.
        $viewerId = $request->user()->id;
        $isPartner = $viewerId !== $user->id
            && Exchange::whereIn('status', ['ongoing', 'completed'])
                ->where(function ($q) use ($viewerId, $user) {
                    $q->where(fn ($a) => $a->where('provider_id', $viewerId)->where('learner_id', $user->id))
                        ->orWhere(fn ($a) => $a->where('provider_id', $user->id)->where('learner_id', $viewerId));
                })
                ->exists();
        $showActivity = $isPartner && ! UserBlock::between($viewerId, $user->id);

        $lessons = Lesson::with(['skillLevel.skill', 'questions.options'])
            ->withCount('ratings')
            ->withAvg('ratings', 'stars')
            ->where('author_id', $user->id)
            ->where('status', 'published')
            ->latest()
            ->get();

        $trusted = $lessons
            ->map(fn (Lesson $l) => $l->skillLevel?->skill)
            ->filter()
            ->unique('id')
            ->filter(fn ($skill) => AuthorTrust::isTrusted($user->id, $skill->id))
            ->pluck('title')
            ->values();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'bio' => $user->bio,
                'availability' => $user->availability,
                'avatar_url' => $user->avatar_url,
                'banner_url' => $user->banner_url,
                'presence' => $user->presence,
                'created_at' => $user->created_at,
            ],
            'activity' => $showActivity
                ? ['presence' => $user->presence, 'last_seen_at' => $user->last_seen_at?->toIso8601String()]
                : null,
            // The level they EARNED (null = listed but not earned yet, so they cannot teach it).
            'skills_offered' => TeachingLevels::offeredPayload($user),
            'skills_wanted' => $user->skillsWanted()->with('skill')->get()->map(fn ($row) => [
                'skill_id' => $row->skill_id,
                'title' => $row->skill?->title,
            ])->values(),
            'average_rating' => round($user->average_rating, 1),
            'ratings_count' => $user->ratingsReceived()->count(),
            'completed_exchanges' => $user->completedExchangesCount(),
            'trusted_skills' => $trusted,
            'lessons' => $lessons->map(fn (Lesson $l) => [
                'id' => $l->id,
                'title' => $l->title,
                'content' => $l->content,
                'skill_id' => $l->skillLevel?->skill_id,
                'level' => $l->skillLevel?->name,
                'created_at' => $l->created_at,
                'ratings_count' => $l->ratings_count,
                'rating' => round((float) $l->ratings_avg_stars, 1),
                'questions' => $l->questions->map(fn ($q) => [
                    'text' => $q->question_text,
                    'options' => $q->options->pluck('option_text')->values(),
                ] + $q->mediaPayload())->values(),
            ])->values(),
        ]);
    }
}
