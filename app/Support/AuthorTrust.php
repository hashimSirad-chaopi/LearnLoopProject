<?php

namespace App\Support;

use App\Models\Lesson;
use App\Models\LessonRating;

/**
 * Trusted-author rule (Option C) — the same numbers as kTrustApprovalsNeeded,
 * kTrustRatingsNeeded and kTrustMinRating in the Flutter app (models.dart).
 *
 * Trust is PER SKILL and is recomputed every time, so an author whose average
 * rating drops below the bar goes back to admin review by itself. Only lessons
 * an admin approved BY HAND (approved_by is set) count toward approvals.
 */
class AuthorTrust
{
    public const APPROVALS_NEEDED = 3;
    public const RATINGS_NEEDED = 5;
    public const MIN_RATING = 4.0;

    /** @return array{approved:int, rating_count:int, average:float, is_trusted:bool} */
    public static function statusFor(int $userId, int $skillId): array
    {
        $lessons = Lesson::query()
            ->where('author_id', $userId)
            ->where('status', 'published')
            ->whereHas('skillLevel', fn ($q) => $q->where('skill_id', $skillId));

        $approved = (clone $lessons)->whereNotNull('approved_by')->count();
        $lessonIds = (clone $lessons)->pluck('id');

        $ratingCount = LessonRating::whereIn('lesson_id', $lessonIds)->count();
        $average = $ratingCount === 0 ? 0.0 : (float) LessonRating::whereIn('lesson_id', $lessonIds)->avg('stars');

        $isTrusted = $approved >= self::APPROVALS_NEEDED
            && $ratingCount >= self::RATINGS_NEEDED
            && $average >= self::MIN_RATING;

        return [
            'approved' => $approved,
            'rating_count' => $ratingCount,
            'average' => round($average, 1),
            'is_trusted' => $isTrusted,
        ];
    }

    public static function isTrusted(int $userId, int $skillId): bool
    {
        return self::statusFor($userId, $skillId)['is_trusted'];
    }
}
