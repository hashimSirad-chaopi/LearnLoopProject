<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LearnLoopCategorySeeder extends Seeder
{
    public function run(): void
    {
        $existingCategories = [
            [
                'old_name' => 'Academics',
                'name' => 'Academic Subjects & Tutoring',
                'description' => 'Learn and teach school, college, and other academic subjects, including mathematics, science, history, and accounting.',
            ],
            [
                'old_name' => 'Arts and Crafts',
                'name' => 'Arts, Crafts & DIY',
                'description' => 'Develop hands-on creative skills through drawing, painting, sewing, knitting, paper crafts, and other do-it-yourself projects.',
            ],
            [
                'old_name' => 'Business and Office Skills',
                'name' => 'Business, Entrepreneurship & Office Tools',
                'description' => 'Build practical skills in entrepreneurship, business planning, bookkeeping, administration, and productivity tools.',
            ],
            [
                'old_name' => 'Cooking and Baking',
                'name' => 'Cooking & Baking',
                'description' => 'Learn food preparation, cooking methods, baking techniques, recipe development, and kitchen fundamentals.',
            ],
            [
                'old_name' => 'Design and Multimedia',
                'name' => 'Design, UI/UX & Multimedia',
                'description' => 'Explore graphic design, illustration, typography, user interface design, user experience, and multimedia production.',
            ],
            [
                'old_name' => 'Fitness and Wellness',
                'name' => 'Fitness & Wellness',
                'description' => 'Exchange knowledge about physical activity, sports skills, mobility, and general wellness practices.',
            ],
            [
                'old_name' => 'Languages',
                'name' => 'Languages & Communication',
                'description' => 'Improve language skills, grammar, pronunciation, writing, conversation, public speaking, and interpersonal communication.',
            ],
            [
                'old_name' => 'Music',
                'name' => 'Music & Performing Arts',
                'description' => 'Practice musical and performance skills, including instruments, singing, music theory, songwriting, and acting.',
            ],
            [
                'old_name' => 'Programming and Web Development',
                'name' => 'Programming & Software Development',
                'description' => 'Learn programming fundamentals, algorithms, software design, debugging, version control, and application development.',
            ],
            [
                'old_name' => 'Test Prep',
                'name' => 'Test Preparation & Study Skills',
                'description' => 'Develop effective study habits, exam preparation methods, revision strategies, note-taking, and practice techniques.',
            ],
        ];

        $newCategories = [
            [
                'name' => 'Web Development',
                'description' => 'Build websites and web applications using HTML, CSS, JavaScript, frontend frameworks, backend technologies, and responsive design.',
            ],
            [
                'name' => 'Mobile App Development',
                'description' => 'Learn to design, build, test, and maintain mobile applications using tools and technologies such as Flutter, Dart, and Android development.',
            ],
            [
                'name' => 'Data, Databases & Analytics',
                'description' => 'Develop skills in SQL, database design, data organization, spreadsheets, data analysis, and data visualization.',
            ],
            [
                'name' => 'AI, Machine Learning & Automation',
                'description' => 'Explore artificial intelligence concepts, machine learning fundamentals, generative AI, and practical task automation.',
            ],
            [
                'name' => 'Digital Marketing & Content Creation',
                'description' => 'Learn content planning, copywriting, search engine optimization, social media marketing, photography, and video production.',
            ],
        ];

        DB::transaction(function () use ($existingCategories, $newCategories) {
            foreach ($existingCategories as $item) {
                // Find the old name on the first run or the new name
                // on later runs. Updating preserves the existing category ID.
                $category = Category::whereIn('name', [
                    $item['old_name'],
                    $item['name'],
                ])->first();

                if ($category) {
                    $category->update([
                        'name' => $item['name'],
                        'description' => $item['description'],
                    ]);
                } else {
                    Category::create([
                        'name' => $item['name'],
                        'description' => $item['description'],
                    ]);
                }
            }

            foreach ($newCategories as $item) {
                Category::updateOrCreate(
                    ['name' => $item['name']],
                    ['description' => $item['description']]
                );
            }
        });
    }
}