
<?php

use App\Models\User;

test('admin cannot change their own role', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    // A second admin ensures this tests self-demotion,
    // not merely the last-admin protection.
    User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $response = $this
        ->actingAs($admin)
        ->from(route('admin.users.edit', $admin))
        ->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'user',
        ]);

    $response
        ->assertRedirect(route('admin.users.edit', $admin))
        ->assertSessionHasErrors('role');

    $this->assertDatabaseHas('users', [
        'id' => $admin->id,
        'role' => 'admin',
    ]);
});