<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Skill;
use App\Models\SkillLevel;
use App\Models\UserLessonAttempt;
use App\Support\AuthorTrust;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Profile -> My Lessons. A member who has passed every lesson of a skill can
 * write lessons for it. They wait for an admin (admin/lessons), unless the
 * author is trusted in that skill, in which case they publish straight away.
 */
class LessonController extends Controller
{
    private const LEVELS = ['easy' => 1, 'moderate' => 2, 'hard' => 3, 'expert' => 4];

    public function index(Request $request)
    {
        $lessons = Lesson::with(['skillLevel.skill', 'questions.options'])
            ->withCount('ratings')
            ->withAvg('ratings', 'stars')
            ->where('author_id', $request->user()->id)
            ->get()
            // Skill -> level -> the order the author arranged (newest last as a tie-break).
            ->sort(fn (Lesson $a, Lesson $b) => [
                $a->skillLevel->skill_id, $a->skillLevel->order, $a->order, $a->id,
            ] <=> [
                $b->skillLevel->skill_id, $b->skillLevel->order, $b->order, $b->id,
            ])
            ->values();

        return response()->json(['lessons' => $lessons]);
    }

    public function store(Request $request)
    {
        $validator = $this->validator($request, true);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $skill = Skill::findOrFail($request->skill_id);
        $userId = $request->user()->id;

        if (! $this->hasPassedEverything($userId, $skill)) {
            return response()->json(['message' => 'Pass every lesson of this skill before you write lessons for it.'], 403);
        }

        $trusted = AuthorTrust::isTrusted($userId, $skill->id);

        $lesson = DB::transaction(function () use ($request, $skill, $userId, $trusted) {
            $level = SkillLevel::firstOrCreate(
                ['skill_id' => $skill->id, 'name' => $request->level],
                ['order' => self::LEVELS[$request->level]]
            );

            $lesson = Lesson::create([
                'skill_level_id' => $level->id,
                'title' => $request->title,
                'content' => $request->content,
                'order' => (int) Lesson::where('skill_level_id', $level->id)->max('order') + 1,
                'author_id' => $userId,
                // Trusted authors skip the queue. approved_by stays NULL, so this lesson
                // does not count toward trust (only hand-approved ones do).
                'status' => $trusted ? 'published' : 'pending',
            ]);

            $this->saveQuestions($lesson, $request->questions);

            return $lesson;
        });

        return response()->json(['lesson' => $lesson->load('skillLevel.skill', 'questions.options')], 201);
    }

    /** Editing sends the lesson back to review (or republishes it for a trusted author). */
    public function update(Request $request, Lesson $lesson)
    {
        if ($lesson->author_id !== $request->user()->id) {
            return response()->json(['message' => 'You can only edit your own lessons'], 403);
        }

        $validator = $this->validator($request, false);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $skillId = $lesson->skillLevel->skill_id;
        $trusted = AuthorTrust::isTrusted($lesson->author_id, $skillId);

        DB::transaction(function () use ($request, $lesson, $skillId, $trusted) {
            $level = SkillLevel::firstOrCreate(
                ['skill_id' => $skillId, 'name' => $request->level],
                ['order' => self::LEVELS[$request->level]]
            );

            // Changing the level puts the lesson at the end of the new level's list.
            $moved = (int) $lesson->skill_level_id !== (int) $level->id;

            $lesson->update([
                'skill_level_id' => $level->id,
                'order' => $moved
                    ? (int) Lesson::where('skill_level_id', $level->id)->max('order') + 1
                    : $lesson->order,
                'title' => $request->title,
                'content' => $request->content,
                'status' => $trusted ? 'published' : 'pending',
                'approved_by' => null,
                'rejection_reason' => null,
                'reviewed_at' => null,
            ]);

            $lesson->questions()->each(fn (Question $q) => $q->delete());
            $this->saveQuestions($lesson, $request->questions);

            // Old stars rated the OLD text; otherwise someone could collect five stars and rewrite the lesson.
            $lesson->ratings()->delete();
        });

        return response()->json(['lesson' => $lesson->fresh(['skillLevel.skill', 'questions.options'])]);
    }

    /**
     * Arrange your own lessons within one skill level (1, 2, 3 ...).
     *
     * The order column is shared with the admin's lessons and other members'
     * lessons in the same level, so the author's lessons only swap among the
     * positions they already hold. They can be re-arranged between themselves
     * but can never jump ahead of (or behind) anybody else's lessons.
     * Order has no effect on review status or ratings, so nothing is reset.
     */
    public function reorder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'skill_id' => 'required|exists:skills,id',
            'level' => 'required|in:'.implode(',', array_keys(self::LEVELS)),
            'lesson_ids' => 'required|array|min:1',
            'lesson_ids.*' => 'integer|distinct',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $level = SkillLevel::where('skill_id', $request->skill_id)->where('name', $request->level)->first();
        if (! $level) {
            return response()->json(['message' => 'You have no lessons in this level'], 422);
        }

        $mine = Lesson::where('skill_level_id', $level->id)
            ->where('author_id', $request->user()->id)
            ->orderBy('order')->orderBy('id')
            ->get();

        $sent = collect($request->lesson_ids)->map(fn ($id) => (int) $id)->values();

        // The list must be exactly the member's own lessons in this level.
        if ($sent->count() !== $mine->count() || $sent->diff($mine->pluck('id'))->isNotEmpty()) {
            return response()->json(['message' => 'The list must contain exactly your lessons in this level'], 422);
        }

        // The positions the author already holds, lowest first (kept strictly increasing).
        $slots = $mine->pluck('order')->map(fn ($n) => (int) $n)->values()->all();
        for ($i = 1; $i < count($slots); $i++) {
            if ($slots[$i] <= $slots[$i - 1]) {
                $slots[$i] = $slots[$i - 1] + 1;
            }
        }

        DB::transaction(function () use ($sent, $slots) {
            foreach ($sent as $i => $id) {
                Lesson::whereKey($id)->update(['order' => $slots[$i]]);
            }
        });

        return response()->json(['message' => 'Order saved']);
    }

    public function destroy(Request $request, Lesson $lesson)
    {
        if ($lesson->author_id !== $request->user()->id) {
            return response()->json(['message' => 'You can only delete your own lessons'], 403);
        }

        $lesson->delete();

        return response()->json(['message' => 'Lesson deleted']);
    }

    /** A learner rates a published member lesson (1-5 stars). Counts toward the author's trust. */
    public function rate(Request $request, Lesson $lesson)
    {
        if ($lesson->status !== 'published' || ! $lesson->author_id) {
            return response()->json(['message' => 'Only published member lessons can be rated'], 422);
        }
        if ($lesson->author_id === $request->user()->id) {
            return response()->json(['message' => 'You cannot rate your own lesson'], 422);
        }

        $validator = Validator::make($request->all(), ['stars' => 'required|integer|min:1|max:5']);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $rating = $lesson->ratings()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['stars' => $request->stars]
        );

        return response()->json(['rating' => $rating], 201);
    }

    private function validator(Request $request, bool $creating)
    {
        return Validator::make($request->all(), [
            'skill_id' => ($creating ? 'required' : 'sometimes').'|exists:skills,id',
            'level' => 'required|in:'.implode(',', array_keys(self::LEVELS)),
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            // My Lessons needs 3 to 10 questions; each has 4 options and one correct answer.
            'questions' => 'required|array|min:3|max:10',
            'questions.*.text' => 'required|string|max:500',
            'questions.*.options' => 'required|array|size:4',
            'questions.*.options.*' => 'required|string|max:255',
            'questions.*.correct' => 'required|integer|between:0,3',
        ]);
    }

    private function hasPassedEverything(int $userId, Skill $skill): bool
    {
        return $skill->hasMasteredBy($userId);
    }

    private function saveQuestions(Lesson $lesson, array $questions): void
    {
        foreach (array_values($questions) as $qi => $q) {
            $question = Question::create([
                'lesson_id' => $lesson->id,
                'question_text' => $q['text'],
                'order' => $qi + 1,
            ]);

            foreach (array_values($q['options']) as $oi => $text) {
                QuestionOption::create([
                    'question_id' => $question->id,
                    'option_text' => $text,
                    'is_correct' => $oi === (int) $q['correct'],
                    'order' => $oi + 1,
                ]);
            }
        }
    }
}
