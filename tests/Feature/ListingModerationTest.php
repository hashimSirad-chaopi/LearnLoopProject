
<?php

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;

test('pending listing can be approved and logged', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $owner = User::factory()->create();
    $category = Category::create(['name' => 'Programming']);

    $listing = Listing::create([
        'user_id' => $owner->id,
        'title' => 'Laravel Basics',
        'category_id' => $category->id,
        'description' => 'Learn Laravel.',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($admin)
        ->from(route('admin.listings'))
        ->patch(route('admin.listings.approve', $listing));

    $response->assertRedirect(route('admin.listings'));

    $this->assertDatabaseHas('listings', [
        'id' => $listing->id,
        'status' => 'active',
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'action' => 'listing.approved',
        'description' => 'Approved listing "Laravel Basics" (pending to active)',
    ]);
});

test('pending listing can be rejected and logged', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $owner = User::factory()->create();
    $category = Category::create(['name' => 'Programming']);

    $listing = Listing::create([
        'user_id' => $owner->id,
        'title' => 'Unacceptable Listing',
        'category_id' => $category->id,
        'description' => 'Listing awaiting review.',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($admin)
        ->from(route('admin.listings'))
        ->patch(route('admin.listings.reject', $listing));

    $response->assertRedirect(route('admin.listings'));

    $this->assertDatabaseHas('listings', [
        'id' => $listing->id,
        'status' => 'inactive',
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'action' => 'listing.rejected',
        'description' => 'Rejected listing "Unacceptable Listing" (pending to inactive)',
    ]);
});

test('pending listing cannot be disabled directly', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $owner = User::factory()->create();
    $category = Category::create(['name' => 'Programming']);

    $listing = Listing::create([
        'user_id' => $owner->id,
        'title' => 'Pending Listing',
        'category_id' => $category->id,
        'description' => 'Awaiting review.',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($admin)
        ->from(route('admin.listings'))
        ->patch(route('admin.listings.disable', $listing));

    $response->assertRedirect(route('admin.listings'))
        ->assertSessionHasErrors('listing');

    $this->assertDatabaseHas('listings', [
        'id' => $listing->id,
        'status' => 'pending',
    ]);

    $this->assertDatabaseMissing('activity_logs', [
        'action' => 'listing.disabled',
        'description' => 'Disabled listing "Pending Listing" (active to inactive)',
    ]);
});

test('active listing can be disabled and logged', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $owner = User::factory()->create();
    $category = Category::create(['name' => 'Programming']);

    $listing = Listing::create([
        'user_id' => $owner->id,
        'title' => 'Active Listing',
        'category_id' => $category->id,
        'description' => 'Currently visible.',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)
        ->from(route('admin.listings'))
        ->patch(route('admin.listings.disable', $listing));

    $response->assertRedirect(route('admin.listings'));

    $this->assertDatabaseHas('listings', [
        'id' => $listing->id,
        'status' => 'inactive',
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'action' => 'listing.disabled',
        'description' => 'Disabled listing "Active Listing" (active to inactive)',
    ]);
});


test('inactive listing can be enabled and logged', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $owner = User::factory()->create();
    $category = Category::create(['name' => 'Programming']);

    $listing = Listing::create([
        'user_id' => $owner->id,
        'title' => 'Disabled Listing',
        'category_id' => $category->id,
        'description' => 'Ready to be re-enabled.',
        'status' => 'inactive',
    ]);

    $response = $this->actingAs($admin)
        ->from(route('admin.listings'))
        ->patch(route('admin.listings.enable', $listing));

    $response->assertRedirect(route('admin.listings'));

    $this->assertDatabaseHas('listings', [
        'id' => $listing->id,
        'status' => 'active',
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'action' => 'listing.enabled',
        'description' => 'Enabled listing "Disabled Listing" (inactive to active)',
    ]);
});

test('active listing cannot be enabled again', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $owner = User::factory()->create();
    $category = Category::create(['name' => 'Programming']);

    $listing = Listing::create([
        'user_id' => $owner->id,
        'title' => 'Already Active Listing',
        'category_id' => $category->id,
        'description' => 'Already visible.',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)
        ->from(route('admin.listings'))
        ->patch(route('admin.listings.enable', $listing));

    $response->assertRedirect(route('admin.listings'))
        ->assertSessionHasErrors('listing');

    $this->assertDatabaseHas('listings', [
        'id' => $listing->id,
        'status' => 'active',
    ]);

    $this->assertDatabaseMissing('activity_logs', [
        'action' => 'listing.enabled',
        'description' => 'Enabled listing "Already Active Listing" (inactive to active)',
    ]);
});

