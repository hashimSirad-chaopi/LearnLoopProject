<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Skill extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description', 'icon', 'status', 'created_by', 'category_id', 'experience_based'];

    protected $casts = ['experience_based' => 'boolean'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function levels(): HasMany
    {
        return $this->hasMany(SkillLevel::class)->orderBy('order');
    }

    /** Every lesson of this skill (all levels). */
    public function lessons()
    {
        return $this->hasManyThrough(Lesson::class, SkillLevel::class);
    }

    /** Total PUBLISHED lessons across all of this skill's levels — used for progress %. Pending or rejected member lessons are not counted. */
    public function totalLessonsCount(): int
    {
        return Lesson::whereIn('skill_level_id', $this->levels()->pluck('id'))->where('status', 'published')->count();
    }

    /** How many of this skill's lessons the given user has passed. */
    public function passedLessonsCountForUser(int $userId): int
    {
        $lessonIds = Lesson::whereIn('skill_level_id', $this->levels()->pluck('id'))->where('status', 'published')->pluck('id');

        return UserLessonAttempt::where('user_id', $userId)
            ->whereIn('lesson_id', $lessonIds)
            ->where('passed', true)
            ->distinct('lesson_id')
            ->count('lesson_id');
    }

    /**
     * Has this member passed every lesson of the skill? Lessons the member wrote
     * themselves do not count either way: otherwise a published lesson of their own
     * would raise the total and lock them out of writing the next one.
     */
    public function hasMasteredBy(int $userId): bool
    {
        $lessonIds = Lesson::whereIn('skill_level_id', $this->levels()->pluck('id'))
            ->where('status', 'published')
            ->where(fn ($q) => $q->whereNull('author_id')->orWhere('author_id', '!=', $userId))
            ->pluck('id');

        if ($lessonIds->isEmpty()) {
            return false;
        }

        $passed = UserLessonAttempt::where('user_id', $userId)
            ->whereIn('lesson_id', $lessonIds)
            ->where('passed', true)
            ->distinct()
            ->pluck('lesson_id');

        return $lessonIds->diff($passed)->isEmpty();
    }

    /** Progress percentage (0–100) for the given user on this skill. */
    public function progressPercentForUser(int $userId): int
    {
        $total = $this->totalLessonsCount();
        if ($total === 0) {
            return 0;
        }

        return (int) round(($this->passedLessonsCountForUser($userId) / $total) * 100);
    }
}
