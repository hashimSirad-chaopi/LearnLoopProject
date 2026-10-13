
<?php

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;

test('category deletion is blocked while listings use it', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $category = Category::create([
        'name' => 'Programming',
    ]);

    $user = User::factory()->create();

    $listing = Listing::create([
        'user_id' => $user->id,
        'title' => 'Laravel Basics',
        'category_id' => $category->id,
        'description' => 'Learn basic Laravel development.',
        'status' => 'pending',
    ]);

    $response = $this
        ->actingAs($admin)
        ->from(route('admin.categories'))
        ->delete(route('admin.categories.delete', $category));

    $response
        ->assertRedirect(route('admin.categories'))
        ->assertSessionHasErrors('category');

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Programming',
    ]);

    $this->assertDatabaseHas('listings', [
        'id' => $listing->id,
        'category_id' => $category->id,
    ]);
});

test('unused category can be deleted', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $category = Category::create([
        'name' => 'Unused Category',
    ]);

    $response = $this
        ->actingAs($admin)
        ->from(route('admin.categories'))
        ->delete(route('admin.categories.delete', $category));

    $response->assertRedirect(route('admin.categories'));

    $this->assertDatabaseMissing('categories', [
        'id' => $category->id,
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'action' => 'category.deleted',
        'description' => 'Deleted category Unused Category',
    ]);
});