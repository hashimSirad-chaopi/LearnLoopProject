
<?php

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use App\Models\Subcategory;

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


test('category deletion is blocked while subcategories exist', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    // This category has no listings, but it does have a subcategory.
    $category = Category::create([
        'name' => 'Programming',
        'description' => 'Programming skills.',
    ]);

    $subcategory = Subcategory::create([
        'category_id' => $category->id,
        'name' => 'Python Programming',
        'description' => 'Learn Python fundamentals.',
    ]);

    $response = $this
        ->actingAs($admin)
        ->from(route('admin.categories'))
        ->delete(route('admin.categories.delete', $category));

    $response
        ->assertRedirect(route('admin.categories'))
        ->assertSessionHasErrors('category');

    // The category must remain.
    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Programming',
    ]);

    // The subcategory must remain as well.
    $this->assertDatabaseHas('subcategories', [
        'id' => $subcategory->id,
        'category_id' => $category->id,
        'name' => 'Python Programming',
    ]);
});
