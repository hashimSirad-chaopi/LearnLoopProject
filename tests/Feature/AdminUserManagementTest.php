<?php

use App\Models\User;

test('an admin cannot demote their own account even when another active admin exists', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $this->actingAs($admin)
        ->withSession(['admin_verified_for' => $admin->id])
        ->from(route('admin.users.edit', $admin))
        ->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'user',
        ])
        ->assertRedirect(route('admin.users.edit', $admin))
        ->assertSessionHasErrors('role');

    expect($admin->fresh()->role)->toBe('admin');
});
