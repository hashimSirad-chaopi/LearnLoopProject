
<?php

use App\Models\Category;
use App\Models\Listing;
use App\Models\Subcategory;
use App\Models\User;

test('editing listing details cannot change its moderation status', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $owner = User::factory()->create();

    $category = Category::create([
        'name' => 'Programming',
        'description' => 'Programming skills.',
    ]);

    $subcategory = Subcategory::create([
        'category_id' => $category->id,
        'name' => 'Python Programming',
        'description' => 'Python fundamentals.',
    ]);

    $listing = Listing::create([
        'user_id' => $owner->id,
        'title' => 'Python Basics',
        'category_id' => $category->id,
        'subcategory_id' => $subcategory->id,
        'level' => 'beginner',
        'description' => 'Original description.',
        'status' => 'pending',
    ]);

    $response = $this
        ->actingAs($admin)
        ->put(route('admin.listings.update', $listing), [
            'user_id' => $owner->id,
            'title' => 'Updated Python Basics',
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'level' => 'intermediate',
            'description' => 'Updated description.',
            'status' => 'active',
        ]);

    $response->assertRedirect(route('admin.listings'));

    $this->assertDatabaseHas('listings', [
        'id' => $listing->id,
        'title' => 'Updated Python Basics',
        'level' => 'intermediate',
        'status' => 'pending',
    ]);
});
