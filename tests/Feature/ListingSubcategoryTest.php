
<?php

use App\Models\Category;
use App\Models\Listing;
use App\Models\Subcategory;
use App\Models\User;

test('admin can create a listing with a matching subcategory and level', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $owner = User::factory()->create();

    $category = Category::create([
        'name' => 'Programming & Software Development',
        'description' => 'Software skills.',
    ]);

    $subcategory = Subcategory::create([
        'category_id' => $category->id,
        'name' => 'Python Programming',
        'description' => 'Python fundamentals.',
    ]);

    $response = $this->actingAs($admin)
        ->post(route('admin.listings.store'), [
            'user_id' => $owner->id,
            'title' => 'Beginner Python Basics',
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'level' => 'beginner',
            'description' => 'Learn variables, conditions, and loops.',
            'status' => 'pending',
        ]);

    $response->assertRedirect(route('admin.listings'));

    $this->assertDatabaseHas('listings', [
        'user_id' => $owner->id,
        'title' => 'Beginner Python Basics',
        'category_id' => $category->id,
        'subcategory_id' => $subcategory->id,
        'level' => 'beginner',
    ]);
});

test('admin cannot create a listing with a subcategory from another category', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $owner = User::factory()->create();

    $programming = Category::create([
        'name' => 'Programming',
        'description' => 'Programming skills.',
    ]);

    $web = Category::create([
        'name' => 'Web Development',
        'description' => 'Website development.',
    ]);

    $css = Subcategory::create([
        'category_id' => $web->id,
        'name' => 'CSS & Responsive Design',
        'description' => 'Web styling.',
    ]);

    $response = $this->actingAs($admin)
        ->from(route('admin.listings.create'))
        ->post(route('admin.listings.store'), [
            'user_id' => $owner->id,
            'title' => 'Invalid Category Combination',
            'category_id' => $programming->id,
            'subcategory_id' => $css->id,
            'level' => 'beginner',
            'description' => 'This should not be saved.',
            'status' => 'pending',
        ]);

    $response->assertRedirect(route('admin.listings.create'))
        ->assertSessionHasErrors('subcategory_id');

    $this->assertDatabaseMissing('listings', [
        'title' => 'Invalid Category Combination',
    ]);
});

test('admin can update a listing category subcategory and level', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $owner = User::factory()->create();

    $programming = Category::create([
        'name' => 'Programming',
        'description' => 'Programming skills.',
    ]);

    $web = Category::create([
        'name' => 'Web Development',
        'description' => 'Website development.',
    ]);

    $python = Subcategory::create([
        'category_id' => $programming->id,
        'name' => 'Python Programming',
        'description' => 'Python fundamentals.',
    ]);

    $css = Subcategory::create([
        'category_id' => $web->id,
        'name' => 'CSS & Responsive Design',
        'description' => 'Web styling.',
    ]);

    $listing = Listing::create([
        'user_id' => $owner->id,
        'title' => 'My Skill Listing',
        'category_id' => $programming->id,
        'subcategory_id' => $python->id,
        'level' => 'beginner',
        'description' => 'An existing skill listing.',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($admin)
        ->put(route('admin.listings.update', $listing), [
            'user_id' => $owner->id,
            'title' => 'Responsive Web Design',
            'category_id' => $web->id,
            'subcategory_id' => $css->id,
            'level' => 'intermediate',
            'description' => 'Learn responsive CSS layouts.',
            'status' => 'active',
        ]);

    $response->assertRedirect(route('admin.listings'));

    $this->assertDatabaseHas('listings', [
        'id' => $listing->id,
        'category_id' => $web->id,
        'subcategory_id' => $css->id,
        'level' => 'intermediate',
        'title' => 'Responsive Web Design',
    ]);
});

test('admin cannot update a listing with a mismatched subcategory', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $owner = User::factory()->create();

    $programming = Category::create([
        'name' => 'Programming',
        'description' => 'Programming skills.',
    ]);

    $web = Category::create([
        'name' => 'Web Development',
        'description' => 'Website development.',
    ]);

    $python = Subcategory::create([
        'category_id' => $programming->id,
        'name' => 'Python Programming',
        'description' => 'Python fundamentals.',
    ]);

    $css = Subcategory::create([
        'category_id' => $web->id,
        'name' => 'CSS & Responsive Design',
        'description' => 'Web styling.',
    ]);

    $listing = Listing::create([
        'user_id' => $owner->id,
        'title' => 'Original Listing',
        'category_id' => $programming->id,
        'subcategory_id' => $python->id,
        'level' => 'beginner',
        'description' => 'Original description.',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($admin)
        ->from(route('admin.listings.edit', $listing))
        ->put(route('admin.listings.update', $listing), [
            'user_id' => $owner->id,
            'title' => 'Invalid Updated Listing',
            'category_id' => $programming->id,
            'subcategory_id' => $css->id,
            'level' => 'advanced',
            'description' => 'This update should be rejected.',
            'status' => 'active',
        ]);

    $response->assertRedirect(route('admin.listings.edit', $listing))
        ->assertSessionHasErrors('subcategory_id');

    $this->assertDatabaseHas('listings', [
        'id' => $listing->id,
        'title' => 'Original Listing',
        'category_id' => $programming->id,
        'subcategory_id' => $python->id,
        'level' => 'beginner',
        'status' => 'pending',
    ]);
});