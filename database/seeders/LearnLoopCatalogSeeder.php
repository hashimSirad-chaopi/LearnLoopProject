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
 * Loads the skill catalog the Flutter app ships with (lib/data/catalog.dart)
 * into the admin database, so the admin manages the SAME skills, levels,
 * lessons and quizzes the members see.
 *
 *   php artisan db:seed --class=LearnLoopCatalogSeeder
 *
 * Safe to run more than once: anything that already exists (matched by
 * category name / skill title / level / lesson title) is left alone.
 * catalog.json was generated from catalog.dart (6 skills, 16 lessons, 32 questions).
 */
class LearnLoopCatalogSeeder extends Seeder
{
    private const LEVEL_ORDER = ['easy' => 1, 'moderate' => 2, 'hard' => 3, 'expert' => 4];

    public function run(): void
    {
        $catalog = json_decode(file_get_contents(__DIR__.'/catalog.json'), true);

        foreach ($catalog['skills'] as $skillData) {
            $category = Category::firstOrCreate(['name' => $skillData['category']]);

            $skill = Skill::firstOrCreate(
                ['title' => $skillData['title']],
                [
                    'description' => $skillData['description'],
                    'icon' => $skillData['icon'],
                    'status' => 'active',
                    'category_id' => $category->id,
                ]
            );

            foreach ($skillData['levels'] as $levelData) {
                $level = SkillLevel::firstOrCreate(
                    ['skill_id' => $skill->id, 'name' => $levelData['name']],
                    ['order' => self::LEVEL_ORDER[$levelData['name']]]
                );

                foreach ($levelData['lessons'] as $i => $lessonData) {
                    $lesson = Lesson::firstOrCreate(
                        ['skill_level_id' => $level->id, 'title' => $lessonData['title']],
                        ['content' => $lessonData['content'], 'order' => $i + 1, 'status' => 'published']
                    );

                    if (! $lesson->wasRecentlyCreated) {
                        continue;
                    }

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
