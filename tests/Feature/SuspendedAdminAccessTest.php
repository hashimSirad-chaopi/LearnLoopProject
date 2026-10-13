
<?php

use App\Models\User;

test('suspended admin is logged out on their next admin request', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $this->actingAs($admin);

    // Simulate another administrator suspending this account.
    $admin->status = 'suspended';
    $admin->save();

    $response = $this->get(route('admin.home'));

    $response->assertRedirect(route('login'));

    $this->assertGuest();
});