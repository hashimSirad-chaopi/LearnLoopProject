<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
<<<<<<< HEAD
        // No default demo or administrator account is created.
        // Use php artisan app:create-admin to create an administrator.
=======
        // User::factory(10)->create();

        // Sample skill categories for the admin's Categories page.
        $this->call(CategorySeeder::class);

        // The skills, levels, lessons and quizzes the Flutter app ships with.
        $this->call(LearnLoopCatalogSeeder::class);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
>>>>>>> 7128c7a35fdb01d62338eda557a779da41476150
    }
}