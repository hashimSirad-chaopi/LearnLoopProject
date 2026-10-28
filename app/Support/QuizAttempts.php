<?php

namespace App\Support;

use App\Models\AppSetting;
use App\Models\Lesson;
use App\Models\UserLessonAttempt;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Daily attempt limit for quizzes.
 *
 * It applies ONLY to the admin's own catalog lessons (lessons.author_id is NULL) taken from
 * Continue Learning. It never applies to:
 *   - lessons a member wrote (Profile > My Lessons), and
 *   - anything done inside an exchange (those attempts carry an exchange_id and are not counted).
 *
 * The limit is per member, per lesson, per day, and depends on the lesson's level (Easy 1, Moderate 2,
 * Hard 3, Expert 1 by default). The admin switches it on/off and edits each level's number in
 * Admin > Settings > Quizzes.
 */
class QuizAttempts
{
    /** Level => default attempts per lesson per day (the admin changes them in Settings > Quizzes). */
    public const DEFAULTS = ['easy' => 1, 'moderate' => 2, 'hard' => 3, 'expert' => 1];

    /** What the admin sees for each level. */
    public const LABELS = ['easy' => 'Beginner (Easy)', 'moderate' => 'Intermediate (Moderate)', 'hard' => 'Hard', 'expert' => 'Expert'];

    public const MAX_LIMIT = 50;

    private static ?bool $ready = null;

    /** False until `php artisan migrate` has added the settings table and the exchange_id column. */
    public static function ready(): bool
    {
        return self::$ready ??= Schema::hasTable('app_settings') && Schema::hasColumn('user_lesson_attempts', 'exchange_id');
    }

    public static function enabled(): bool
    {
        return self::ready() && AppSetting::read('quiz_attempt_limit_enabled', '1') !== '0';
    }

    /** Attempts per day for one level, e.g. 'hard' => 3. Unknown levels use the Easy number. */
    public static function limitFor(?string $level): int
    {
        $level = strtolower((string) $level);
        $level = array_key_exists($level, self::DEFAULTS) ? $level : 'easy';
        $n = (int) AppSetting::read('quiz_attempts_'.$level, (string) self::DEFAULTS[$level]);

        return max(1, min(self::MAX_LIMIT, $n ?: self::DEFAULTS[$level]));
    }

    /** @return array<string,int> level => attempts per day */
    public static function limits(): array
    {
        $out = [];
        foreach (array_keys(self::DEFAULTS) as $level) {
            $out[$level] = self::limitFor($level);
        }

        return $out;
    }

    /** The limit in force for a lesson (by its level), or null when switched off. */
    public static function activeLimit(?string $level): ?int
    {
        return self::enabled() ? self::limitFor($level) : null;
    }

    public static function appliesTo(Lesson $lesson): bool
    {
        return $lesson->author_id === null;
    }

    /** "Today" runs midnight to midnight in the configured quiz timezone. */
    public static function dayStart(): Carbon
    {
        $tz = (string) config('learnloop.quiz_day_timezone', 'Asia/Manila');

        return Carbon::now($tz)->startOfDay()->setTimezone(config('app.timezone'));
    }

    public static function resetsAt(): Carbon
    {
        return self::dayStart()->copy()->addDay();
    }

    /** Attempts this member made today on one lesson (exchange attempts excluded). */
    public static function usedToday(int $userId, int $lessonId): int
    {
        return self::query($userId)->where('lesson_id', $lessonId)->count();
    }

    /** @return array<int,int> lesson_id => attempts made today, for several lessons at once */
    public static function usedTodayFor(int $userId, array $lessonIds): array
    {
        if ($lessonIds === []) {
            return [];
        }

        return self::query($userId)->whereIn('lesson_id', $lessonIds)
            ->selectRaw('lesson_id, COUNT(*) as n')->groupBy('lesson_id')
            ->pluck('n', 'lesson_id')->map(fn ($n) => (int) $n)->all();
    }

    public static function left(?int $limit, int $used): ?int
    {
        return $limit === null ? null : max(0, $limit - $used);
    }

    private static function query(int $userId)
    {
        return UserLessonAttempt::where('user_id', $userId)
            ->whereNull('exchange_id')
            ->where(fn ($q) => $q->where('attempted_at', '>=', self::dayStart())
                ->orWhere(fn ($w) => $w->whereNull('attempted_at')->where('created_at', '>=', self::dayStart())));
    }
}
