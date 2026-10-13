<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('admin creation command creates an active administrator', function () {
    $this->artisan('app:create-admin')
        ->expectsQuestion('Admin name', 'Demo Admin')
        ->expectsQuestion('Admin email', 'demo-admin@example.com')
        ->expectsQuestion('Admin password', 'demo-password-123')
        ->expectsQuestion('Confirm password', 'demo-password-123')
        ->assertExitCode(0);

    $admin = User::where(
        'email',
        'demo-admin@example.com'
    )->firstOrFail();

    expect($admin->name)->toBe('Demo Admin');
    expect($admin->role)->toBe('admin');
    expect($admin->status)->toBe('active');

    expect(
        Hash::check('demo-password-123', $admin->password)
    )->toBeTrue();
});