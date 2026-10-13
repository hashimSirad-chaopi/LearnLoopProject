
<?php

use App\Models\Category;
use App\Models\Listing;
use App\Models\Subcategory;
use App\Models\User;

function createSubcategoryAdmin(): User
{
    return User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);
}

function createSubcategoryCategory(string $name = 'Programming'): Category
{
    return Category::create([
        'name' => $name,
        'description' => 'A category for learning skills.',
    ]);
}

test('admin can create a subcategory and record the action', function () {
    $admin = createSubcategoryAdmin();
    $category = createSubcategoryCategory();

    $response = $this
        ->actingAs($admin)
        ->from(route('admin.subcategories'))
        ->post(route('admin.subcategories.store'), [
            'category_id' => $category->id,
            'name' => 'Python Programming',
            'description' => 'Learn Python fundamentals.',
        ]);

    $response->assertRedirect(route('admin.subcategories'));

    $this->assertDatabaseHas('subcategories', [
        'category_id' => $category->id,
        'name' => 'Python Programming',
        'description' => 'Learn Python fundamentals.',
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'action' => 'subcategory.created',
        'description' => 'Created subcategory "Python Programming" under "Programming"',
    ]);
});

test('admin cannot create duplicate subcategory names in one category', function () {
    $admin = createSubcategoryAdmin();
    $category = createSubcategoryCategory();

    Subcategory::create([
        'category_id' => $category->id,
        'name' => 'Python Programming',
        'description' => 'Existing skill.',
    ]);

    $response = $this
        ->actingAs($admin)
        ->from(route('admin.subcategories'))
        ->post(route('admin.subcategories.store'), [
            'category_id' => $category->id,
            'name' => 'Python Programming',
            'description' => 'Duplicate skill.',
        ]);

    $response
        ->assertRedirect(route('admin.subcategories'))
        ->assertSessionHasErrors('name');

    expect(
        Subcategory::where('category_id', $category->id)
            ->where('name', 'Python Programming')
            ->count()
    )->toBe(1);
});

test('admin can edit a subcategory and record the change', function () {
    $admin = createSubcategoryAdmin();
    $category = createSubcategoryCategory();

    $subcategory = Subcategory::create([
        'category_id' => $category->id,
        'name' => 'Python Basics',
        'description' => 'An introductory skill.',
    ]);

    $response = $this
        ->actingAs($admin)
        ->from(route('admin.subcategories'))
        ->put(route('admin.subcategories.update', $subcategory), [
            'category_id' => $category->id,
            'name' => 'Python for Beginners',
            'description' => 'Variables, loops, and functions.',
        ]);

    $response->assertRedirect(route('admin.subcategories'));

    $this->assertDatabaseHas('subcategories', [
        'id' => $subcategory->id,
        'name' => 'Python for Beginners',
        'description' => 'Variables, loops, and functions.',
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'action' => 'subcategory.updated',
        'description' => 'Updated subcategory "Python Basics" to "Python for Beginners" under "Programming"',
    ]);
});

test('admin cannot delete a subcategory used by a listing', function () {
    $admin = createSubcategoryAdmin();
    $owner = User::factory()->create();
    $category = createSubcategoryCategory();

    $subcategory = Subcategory::create([
        'category_id' => $category->id,
        'name' => 'Python Programming',
        'description' => 'Learn Python.',
    ]);

    $listing = Listing::create([
        'user_id' => $owner->id,
        'title' => 'Beginner Python',
        'category_id' => $category->id,
        'subcategory_id' => $subcategory->id,
        'level' => 'beginner',
        'description' => 'Learn Python fundamentals.',
        'status' => 'pending',
    ]);

    $response = $this
        ->actingAs($admin)
        ->from(route('admin.subcategories'))
        ->delete(route('admin.subcategories.delete', $subcategory));

    $response
        ->assertRedirect(route('admin.subcategories'))
        ->assertSessionHasErrors('subcategory');

    $this->assertDatabaseHas('subcategories', [
        'id' => $subcategory->id,
    ]);

    $this->assertDatabaseHas('listings', [
        'id' => $listing->id,
        'subcategory_id' => $subcategory->id,
    ]);
});

test('admin can delete an unused subcategory and record the action', function () {
    $admin = createSubcategoryAdmin();
    $category = createSubcategoryCategory();

    $subcategory = Subcategory::create([
        'category_id' => $category->id,
        'name' => 'Unused Skill',
        'description' => 'A skill with no listings.',
    ]);

    $response = $this
        ->actingAs($admin)
        ->from(route('admin.subcategories'))
        ->delete(route('admin.subcategories.delete', $subcategory));

    $response->assertRedirect(route('admin.subcategories'));

    $this->assertDatabaseMissing('subcategories', [
        'id' => $subcategory->id,
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'action' => 'subcategory.deleted',
        'description' => 'Deleted subcategory "Unused Skill" from "Programming"',
    ]);
});

test('admin cannot move a subcategory used by a listing to another category', function () {
    $admin = createSubcategoryAdmin();

    $programming = createSubcategoryCategory('Programming');
    $web = createSubcategoryCategory('Web Development');

    $subcategory = Subcategory::create([
        'category_id' => $programming->id,
        'name' => 'Python Programming',
        'description' => 'Learn Python fundamentals.',
    ]);

    $owner = User::factory()->create();

    $listing = Listing::create([
        'user_id' => $owner->id,
        'title' => 'Beginner Python',
        'category_id' => $programming->id,
        'subcategory_id' => $subcategory->id,
        'level' => 'beginner',
        'description' => 'Learn Python basics.',
        'status' => 'pending',
    ]);

    $response = $this
        ->actingAs($admin)
        ->from(route('admin.subcategories'))
        ->put(route('admin.subcategories.update', $subcategory), [
            'category_id' => $web->id,
            'name' => 'Python Programming',
            'description' => 'Learn Python fundamentals.',
        ]);

    $response
        ->assertRedirect(route('admin.subcategories'))
        ->assertSessionHasErrors('category_id');

    $this->assertDatabaseHas('subcategories', [
        'id' => $subcategory->id,
        'category_id' => $programming->id,
    ]);

    $this->assertDatabaseHas('listings', [
        'id' => $listing->id,
        'category_id' => $programming->id,
        'subcategory_id' => $subcategory->id,
    ]);
});