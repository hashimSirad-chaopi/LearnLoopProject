<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SkillLevel extends Model
{
    use HasFactory;

    protected $fillable = ['skill_id', 'name', 'order'];

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }

    /** Has the given user passed every lesson in this level? Used to unlock the next tier. */
    public function isCompletedByUser(int $userId): bool
    {
        $lessonIds = $this->lessons()->pluck('id');
        if ($lessonIds->isEmpty()) {
            return false;
        }

        $passedCount = UserLessonAttempt::where('user_id', $userId)
            ->whereIn('lesson_id', $lessonIds)
            ->where('passed', true)
            ->distinct('lesson_id')
            ->count('lesson_id');

        return $passedCount === $lessonIds->count();
    }
}
