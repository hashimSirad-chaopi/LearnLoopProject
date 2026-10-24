<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * A sample list of skill categories for the admin (Categories page) and for
 * the category picker when members create listings.
 *
 *   php artisan db:seed --class=CategorySeeder
 *
 * Safe to run more than once: a category that already exists (matched by name)
 * is left alone, so it never duplicates the ones LearnLoopCatalogSeeder adds
 * (Language, Programming Language, Design, Music).
 */
class CategorySeeder extends Seeder
{
    public const NAMES = [
        // Already used by the sample skills in the app.
        'Language',
        'Programming Language',
        'Design',
        'Music',

        // Creative
        'Photography & Video',
        'Arts & Crafts',
        'Dance',
        'Writing & Storytelling',
        'Fashion & Beauty',

        // Practical
        'Cooking & Baking',
        'Home & DIY',
        'Gardening',
        'Automotive',

        // Learning & career
        'Academic Tutoring',
        'Mathematics & Science',
        'Business & Finance',
        'Digital Marketing',
        'Public Speaking',
        'Technology & Gadgets',

        // Life
        'Fitness & Sports',
        'Health & Wellness',
        'Personal Development',
        'Travel & Culture',
        'Games & Hobbies',
    ];

    public function run(): void
    {
        foreach (self::NAMES as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
    }
}
