<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('a member can register through the API', function () {
    $email = 'newmember@example.com';
    $password = 'SecureTestPassword123!';

    $response = $this->postJson('/api/register', [
        'name' => 'New Member',
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $password,

        // Client-supplied privilege fields must not be trusted.
        'role' => 'admin',
        'status' => 'suspended',
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'user' => [
                'id',
                'name',
                'email',
                'role',
                'status',
            ],
            'token',
        ])
        ->assertJsonPath('user.name', 'New Member')
        ->assertJsonPath('user.email', $email)
        ->assertJsonPath('user.role', 'user')
        ->assertJsonPath('user.status', 'active');

    $user = User::where('email', $email)->firstOrFail();

    expect(Hash::check($password, $user->password))->toBeTrue();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'role' => 'user',
        'status' => 'active',
    ]);
});

test('API registration rejects mismatched password confirmation', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'New Member',
        'email' => 'newmember@example.com',
        'password' => 'SecureTestPassword123!',
        'password_confirmation' => 'DifferentPassword123!',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);

    $this->assertDatabaseMissing('users', [
        'email' => 'newmember@example.com',
    ]);
});