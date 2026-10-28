<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use App\Support\QuizAttempts;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    /**
     * The catalog the Flutter Learn tab shows (replaces lib/data/catalog.dart).
     * Only active skills, and only PUBLISHED lessons: a member's pending or
     * rejected lesson is never shown to learners.
     *
     * The lessons a member wrote themselves are left out of THEIR copy of the course: they live
     * in Profile > My Lessons, and never count toward (or sit inside) the lessons they study.
     *
     * The right answer is NOT sent: quizzes are graded on the server
     * (POST /lessons/{id}/attempts with the member's answers), otherwise anyone
     * could read the answers off the wire and "earn" a teaching level.
     */
    public function index(Request $request)
    {
        $me = $request->user()->id;

        $skills = Skill::with([
            'category',
            'levels.lessons' => fn ($q) => $q->where('status', 'published')
                ->where(fn ($w) => $w->whereNull('author_id')->orWhere('author_id', '!=', $me)),
            'levels.lessons.questions.options',
        ])->where('status', 'active')->orderBy('title')->get();

        // Daily quiz attempts: only the admin's own lessons are limited (see App\Support\QuizAttempts).
        $enabled = QuizAttempts::enabled();
        $used = ! $enabled ? [] : QuizAttempts::usedTodayFor(
            $me,
            $skills->flatMap(fn (Skill $s) => $s->levels->flatMap(fn ($l) => $l->lessons->whereNull('author_id')->pluck('id')))->all()
        );
        $resetsAt = ! $enabled ? null : QuizAttempts::resetsAt()->toIso8601String();

        return response()->json(['skills' => $skills->map(fn (Skill $skill) => [
            'id' => $skill->id,
            'title' => $skill->title,
            'category' => $skill->category?->name,
            'icon' => $skill->icon,
            'description' => $skill->description,
            'experience_based' => (bool) $skill->experience_based,
            'levels' => $skill->levels->map(fn ($level) => [
                'name' => $level->name,
                'lessons' => $level->lessons->map(fn ($lesson) => [
                    'id' => $lesson->id,
                    'attempt_limit' => $lesson->author_id === null ? QuizAttempts::activeLimit($level->name) : null,
                    'attempts_left' => $lesson->author_id === null ? QuizAttempts::left(QuizAttempts::activeLimit($level->name), $used[$lesson->id] ?? 0) : null,
                    'attempts_resets_at' => $lesson->author_id === null ? $resetsAt : null,
                    'title' => $lesson->title,
                    'content' => $lesson->content,
                    'author_id' => $lesson->author_id,
                    'content_type' => $lesson->content_type ?: 'text',
                    'attachment_url' => $lesson->attachment_url,
                    'attachment_name' => $lesson->attachment_name,
                    'questions' => $lesson->questions->map(fn ($q) => [
                        'text' => $q->question_text,
                        'options' => $q->options->pluck('option_text')->values(),
                    ] + $q->mediaPayload())->values(),
                ])->values(),
            ])->values(),
        ])->values()]);
    }
}
