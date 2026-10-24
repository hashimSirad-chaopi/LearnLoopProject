<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Skill;
use App\Models\SkillLevel;
use Illuminate\Database\Seeder;

/**
 * Adds Moderate, Hard and Expert lessons (each with a short quiz) to the six
 * core skills: Conversational Spanish, Intro to Python, Photoshop Basics,
 * Flutter Fundamentals, Guitar Basics and UI/UX Design Basics.
 *
 *   php artisan db:seed --class=AdvancedLessonsSeeder
 *
 * 36 lessons and 96 questions in total. Every skill ends up with 12 Hard and
 * Expert questions, more than the 5 the Professional quiz needs to be ready.
 *
 * Safe to run more than once: a lesson that already exists (same skill, level
 * and title) is left alone. If a skill already exists under an older name
 * (for example "Spanish"), it is reused instead of creating a duplicate.
 * Reads advanced_lessons.json.
 */
class AdvancedLessonsSeeder extends Seeder
{
    private const LEVEL_ORDER = ['easy' => 1, 'moderate' => 2, 'hard' => 3, 'expert' => 4];

    public function run(): void
    {
        $catalog = json_decode(file_get_contents(__DIR__.'/advanced_lessons.json'), true);

        foreach ($catalog['skills'] as $skillData) {
            $skill = Skill::whereIn('title', array_merge([$skillData['title']], $skillData['aliases'] ?? []))
                ->orderBy('id')
                ->first();

            if (! $skill) {
                $category = Category::firstOrCreate(['name' => $skillData['category']]);
                $skill = Skill::create([
                    'title' => $skillData['title'],
                    'description' => $skillData['description'],
                    'icon' => $skillData['icon'],
                    'status' => 'active',
                    'category_id' => $category->id,
                ]);
            }

            foreach ($skillData['levels'] as $levelData) {
                $level = SkillLevel::firstOrCreate(
                    ['skill_id' => $skill->id, 'name' => $levelData['name']],
                    ['order' => self::LEVEL_ORDER[$levelData['name']]]
                );

                foreach ($levelData['lessons'] as $lessonData) {
                    $exists = Lesson::where('skill_level_id', $level->id)
                        ->where('title', $lessonData['title'])
                        ->exists();
                    if ($exists) {
                        continue;
                    }

                    $lesson = Lesson::create([
                        'skill_level_id' => $level->id,
                        'title' => $lessonData['title'],
                        'content' => $lessonData['content'],
                        'order' => ((int) Lesson::where('skill_level_id', $level->id)->max('order')) + 1,
                        'status' => 'published',
                        'content_type' => 'text',
                    ]);

                    foreach ($lessonData['questions'] as $qi => $q) {
                        $question = Question::create([
                            'lesson_id' => $lesson->id,
                            'question_text' => $q['text'],
                            'order' => $qi + 1,
                        ]);

                        foreach ($q['options'] as $oi => $text) {
                            QuestionOption::create([
                                'question_id' => $question->id,
                                'option_text' => $text,
                                'is_correct' => $oi === $q['correct'],
                                'order' => $oi + 1,
                            ]);
                        }
                    }
                }
            }
        }
    }
}
