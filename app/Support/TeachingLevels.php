<?php

namespace App\Support;

use App\Models\Lesson;
use App\Models\ProfessionalVerification;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserLessonAttempt;
use App\Models\UserSkill;
use Illuminate\Support\Collection;

/**
 * The rule from Needs_Improvement.docx: a member may only TEACH a skill at a
 * level they have EARNED.
 *
 *   easy -> moderate -> hard -> expert   earned by passing every lesson quiz of that level,
 *                                        in order (a level opens when the one before is done).
 *   professional                         earned by sending proof + passing the hard quiz + an
 *                                        admin approving it (ProfessionalVerification).
 *
 * Example: someone who finished Spanish "Easy" can teach Spanish on Easy only. To teach
 * Moderate they must grind the Moderate lessons too.
 *
 * Lessons the member wrote themselves never count for or against them (same rule as
 * Skill::hasMasteredBy), and levels that have no lessons yet are skipped.
 */
class TeachingLevels
{
    /** Lowest to highest. Professional outranks everything: it is proven, not grinded. */
    public const ORDER = ['easy', 'moderate', 'hard', 'expert', 'professional'];

    /** The four levels that are earned by lessons. */
    public const GRINDED = ['easy', 'moderate', 'hard', 'expert'];

    public const PASS_RATIO = 0.7;

    public static function rank(?string $level): int
    {
        $i = array_search($level, self::ORDER, true);

        return $i === false ? -1 : $i;
    }

    /** Has someone with `$earned` the right to teach at `$required`? */
    public static function covers(?string $earned, ?string $required): bool
    {
        if ($earned === null) {
            return false;
        }

        return self::rank($earned) >= self::rank($required ?: 'easy');
    }

    public static function label(?string $level): string
    {
        return $level ? ucfirst($level) : 'No level yet';
    }

    public static function passMark(int $total): int
    {
        return (int) ceil($total * self::PASS_RATIO);
    }

    /**
     * The whole picture for one member and one skill.
     *
     * @return array{
     *   skill_id:int, title:string, earned_level:?string, professional:?string,
     *   lessons_total:int, lessons_passed:int, percent:int, next_level:?string,
     *   levels:list<array{name:string,total:int,passed:int,percent:int,complete:bool,unlocked:bool}>
     * }
     */
    public static function summary(int $userId, Skill $skill, ?Collection $passedIds = null, ?string $proStatus = null, bool $proKnown = false): array
    {
        $skill->loadMissing('levels');

        $levels = $skill->levels->load(['lessons' => fn ($q) => $q->where('status', 'published')
            ->where(fn ($w) => $w->whereNull('author_id')->orWhere('author_id', '!=', $userId))]);

        if ($passedIds === null) {
            $lessonIds = $levels->flatMap(fn ($l) => $l->lessons->pluck('id'));
            $passedIds = UserLessonAttempt::where('user_id', $userId)
                ->whereIn('lesson_id', $lessonIds)
                ->where('passed', true)
                ->pluck('lesson_id')
                ->unique()
                ->values();
        }

        if (! $proKnown) {
            $proStatus = ProfessionalVerification::where('user_id', $userId)->where('skill_id', $skill->id)->value('status');
        }

        $out = [];
        $earned = null;
        $chainOpen = true; // every earlier level (that has lessons) is complete
        $totalAll = 0;
        $passedAll = 0;
        $next = null;

        foreach ($levels as $level) {
            $ids = $level->lessons->pluck('id');
            $total = $ids->count();
            $passed = $ids->intersect($passedIds)->count();
            $complete = $total > 0 && $passed === $total;

            $totalAll += $total;
            $passedAll += $passed;

            $out[] = [
                'name' => $level->name,
                'total' => $total,
                'passed' => $passed,
                'percent' => $total === 0 ? 0 : (int) round($passed / $total * 100),
                'complete' => $complete,
                'unlocked' => $total > 0 && $chainOpen,
            ];

            if ($total === 0) {
                continue; // a level with no lessons is skipped, it neither blocks nor grants
            }
            if ($chainOpen && $complete) {
                $earned = $level->name;
            } elseif ($chainOpen && $next === null) {
                $next = $level->name; // the level they are working on now
            }
            if (! $complete) {
                $chainOpen = false;
            }
        }

        // Experience skills (dancing, singing...) have no ladder: Professional is the only level,
        // and only an approved certification grants it.
        if ($skill->experience_based) {
            $earned = null;
            $next = null;
        }

        return [
            'skill_id' => $skill->id,
            'title' => $skill->title,
            'experience_based' => (bool) $skill->experience_based,
            'earned_level' => $proStatus === 'approved' ? 'professional' : $earned,
            'grinded_level' => $earned,
            'professional' => $proStatus,
            'lessons_total' => $totalAll,
            'lessons_passed' => $passedAll,
            'percent' => $totalAll === 0 ? 0 : (int) round($passedAll / $totalAll * 100),
            'next_level' => $next,
            'levels' => $out,
        ];
    }

    /** The level this member may teach this skill at, or null when they have earned none yet. */
    public static function earned(int $userId, Skill|int $skill): ?string
    {
        $skill = $skill instanceof Skill ? $skill : Skill::find($skill);

        return $skill ? self::summary($userId, $skill)['earned_level'] : null;
    }

    public static function canTeach(int $userId, Skill|int $skill, ?string $level = null): bool
    {
        return self::covers(self::earned($userId, $skill), $level);
    }

    /** Summaries for every active skill, using three queries instead of three per skill. */
    public static function all(int $userId): Collection
    {
        $skills = Skill::with('levels')->where('status', 'active')->orderBy('title')->get();

        $passed = UserLessonAttempt::where('user_id', $userId)->where('passed', true)->pluck('lesson_id')->unique()->values();
        $pro = ProfessionalVerification::where('user_id', $userId)->pluck('status', 'skill_id');

        return $skills->map(fn (Skill $s) => self::summary($userId, $s, $passed, $pro[$s->id] ?? null, true))->values();
    }

    /**
     * May this member open this lesson's quiz yet? Lessons are taken in order
     * (level by level, lesson by lesson): every lesson before it must be passed.
     */
    public static function isLessonUnlocked(int $userId, Lesson $lesson): bool
    {
        $lesson->loadMissing('skillLevel');
        $skillId = $lesson->skillLevel?->skill_id;
        if (! $skillId) {
            return true;
        }
        if ($lesson->author_id === $userId) {
            return true; // your own lesson is not part of your climb
        }

        $before = Lesson::query()
            ->join('skill_levels', 'skill_levels.id', '=', 'lessons.skill_level_id')
            ->where('skill_levels.skill_id', $skillId)
            ->where('lessons.status', 'published')
            ->where(fn ($w) => $w->whereNull('lessons.author_id')->orWhere('lessons.author_id', '!=', $userId))
            ->orderBy('skill_levels.order')->orderBy('lessons.order')->orderBy('lessons.id')
            ->get(['lessons.id'])
            ->pluck('id');

        $position = $before->search($lesson->id);
        if ($position === false || $position === 0) {
            return true;
        }

        $needed = $before->take($position);
        $done = UserLessonAttempt::where('user_id', $userId)->whereIn('lesson_id', $needed)->where('passed', true)
            ->pluck('lesson_id')->unique();

        return $needed->diff($done)->isEmpty();
    }

    /**
     * Keep the stored `user_skills.level` of the skills a member teaches equal to what they have
     * earned, so the admin panel and old queries read the same truth as the API.
     */
    public static function syncOffered(int $userId, ?int $onlySkillId = null): void
    {
        $rows = UserSkill::where('user_id', $userId)->where('type', 'offered')
            ->when($onlySkillId, fn ($q) => $q->where('skill_id', $onlySkillId))
            ->get();

        foreach ($rows as $row) {
            $level = self::earned($userId, $row->skill_id);
            if ($row->level !== $level) {
                $row->update(['level' => $level]);
            }
        }
    }

    /**
     * The `skills_offered` block of GET /user, GET /users/{id} and PUT /profile.
     * `level` is the EARNED level; `locked` means they list the skill but have not earned Easy yet.
     */
    public static function offeredPayload(User $user): Collection
    {
        return $user->skillsOffered()->with('skill')->get()->map(function (UserSkill $row) use ($user) {
            $level = self::earned($user->id, $row->skill_id);

            return [
                'id' => $row->id,
                'skill_id' => $row->skill_id,
                'type' => 'offered',
                'title' => $row->skill?->title,
                'skill' => $row->skill ? ['id' => $row->skill->id, 'title' => $row->skill->title] : null,
                'level' => $level,
                'locked' => $level === null,
            ];
        })->values();
    }

    /** Normalise the old Flutter level names. */
    public static function normalise(?string $level): ?string
    {
        return $level === null ? null : (UserSkill::LEGACY[$level] ?? $level);
    }
}
