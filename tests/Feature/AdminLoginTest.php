<?php

use App\Models\User;

// The factory's default password is "password".

function makeAdmin(array $attributes = []): User
{
    return User::factory()->create(array_merge(['role' => 'admin', 'status' => 'active'], $attributes));
}

test('the login page renders', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Admin Panel');
});

test('an active admin can log in and lands on the admin home', function () {
    $admin = makeAdmin();

    $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('admin.home'));

    $this->assertAuthenticatedAs($admin);
});

test('a wrong password is rejected', function () {
    $admin = makeAdmin();

    $this->post('/login', ['email' => $admin->email, 'password' => 'wrong'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('an unknown email gets the same message as a wrong password', function () {
    $admin = makeAdmin();
    $message = 'These credentials do not match our records.';

    $this->post('/login', ['email' => 'nobody@example.com', 'password' => 'password'])
        ->assertSessionHasErrors(['email' => $message]);

    $this->post('/login', ['email' => $admin->email, 'password' => 'wrong'])
        ->assertSessionHasErrors(['email' => $message]);

    $this->assertGuest();
});

test('a non-admin with the right password is rejected and never signed in', function () {
    $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'This account does not have admin access.']);

    $this->assertGuest();
});

test('a suspended admin is rejected and never signed in', function () {
    $admin = makeAdmin(['status' => 'suspended']);

    $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('login is blocked after five failed attempts, even with the right password', function () {
    $admin = makeAdmin();

    foreach (range(1, 5) as $i) {
        $this->post('/login', ['email' => $admin->email, 'password' => 'wrong']);
    }

    $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('a successful login clears the failed-attempt counter', function () {
    $admin = makeAdmin();

    foreach (range(1, 3) as $i) {
        $this->post('/login', ['email' => $admin->email, 'password' => 'wrong']);
    }

    $this->post('/login', ['email' => $admin->email, 'password' => 'password']);
    $this->assertAuthenticatedAs($admin);

    $this->post('/logout');

    foreach (range(1, 4) as $i) {
        $this->post('/login', ['email' => $admin->email, 'password' => 'wrong']);
    }

    // 4 new failures (not 7) -> still allowed to try
    $this->post('/login', ['email' => $admin->email, 'password' => 'password']);
    $this->assertAuthenticatedAs($admin);
});

test('logging out returns to the login page', function () {
    $admin = makeAdmin();

    $this->actingAs($admin)
        ->post('/logout')
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('guests are sent to the login page when opening the admin panel', function () {
    $this->get('/admin/home')->assertRedirect('/login');
});

test('a logged-in non-admin gets a 403 on the admin panel', function () {
    $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

    $this->actingAs($user)->get('/admin/home')->assertForbidden();
});
