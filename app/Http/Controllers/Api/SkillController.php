<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Skill;

class SkillController extends Controller
{
    /**
     * The catalog the Flutter Learn tab shows (replaces lib/data/catalog.dart).
     * Only active skills, and only PUBLISHED lessons: a member's pending or
     * rejected lesson is never shown to learners.
     *
     * The right answer is NOT sent: quizzes are graded on the server
     * (POST /lessons/{id}/attempts with the member's answers), otherwise anyone
     * could read the answers off the wire and "earn" a teaching level.
     */
    public function index()
    {
        $skills = Skill::with([
            'category',
            'levels.lessons' => fn ($q) => $q->where('status', 'published'),
            'levels.lessons.questions.options',
        ])->where('status', 'active')->orderBy('title')->get();

        return response()->json(['skills' => $skills->map(fn (Skill $skill) => [
            'id' => $skill->id,
            'title' => $skill->title,
            'category' => $skill->category?->name,
            'icon' => $skill->icon,
            'description' => $skill->description,
            'levels' => $skill->levels->map(fn ($level) => [
                'name' => $level->name,
                'lessons' => $level->lessons->map(fn ($lesson) => [
                    'id' => $lesson->id,
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
