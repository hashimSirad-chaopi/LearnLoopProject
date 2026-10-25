<?php

use App\Models\User;

function makeBulkUserAdmin(): User
{
    return User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);
}

function makeBulkUserMember(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'user',
        'status' => 'active',
    ], $attributes));
}

test('bulk suspension skips administrators and suspends eligible members', function () {
    $admin = makeBulkUserAdmin();

    $otherAdmin = makeBulkUserAdmin();

    $member = makeBulkUserMember();

    $this->actingAs($admin)
        ->withSession(['admin_verified_for' => $admin->id])
        ->from(route('admin.users'))
        ->post(route('admin.users.bulk'), [
            'ids' => [$admin->id, $otherAdmin->id, $member->id],
            'bulk' => 'suspend',
            'duration' => '1w',
            'reason' => 'spam',
        ])
        ->assertRedirect(route('admin.users'))
        ->assertSessionHas('success');

    expect($admin->fresh()->status)->toBe('active');
    expect($otherAdmin->fresh()->status)->toBe('active');
    expect($member->fresh()->status)->toBe('suspended');
    expect($member->fresh()->suspension_reason)->toBe('spam');

    $this->assertDatabaseHas('activity_logs', [
        'action' => 'user.suspended',
        'user_id' => $admin->id,
    ]);
});

test('bulk reactivation clears suspension details for eligible members', function () {
    $admin = makeBulkUserAdmin();

    $member = makeBulkUserMember([
        'status' => 'suspended',
        'suspended_until' => now()->addWeek(),
        'suspension_reason' => 'spam',
        'suspension_note' => 'Repeated unwanted posts',
    ]);

    $this->actingAs($admin)
        ->withSession(['admin_verified_for' => $admin->id])
        ->from(route('admin.users'))
        ->post(route('admin.users.bulk'), [
            'ids' => [$member->id],
            'bulk' => 'activate',
        ])
        ->assertRedirect(route('admin.users'))
        ->assertSessionHas('success');

    $member->refresh();

    expect($member->status)->toBe('active');
    expect($member->suspended_until)->toBeNull();
    expect($member->suspension_reason)->toBeNull();
    expect($member->suspension_note)->toBeNull();

    $this->assertDatabaseHas('activity_logs', [
        'action' => 'user.activated',
        'user_id' => $admin->id,
    ]);
});

test('bulk suspension requires a note when the reason is other', function () {
    $admin = makeBulkUserAdmin();
    $member = makeBulkUserMember();

    $this->actingAs($admin)
        ->withSession(['admin_verified_for' => $admin->id])
        ->from(route('admin.users'))
        ->post(route('admin.users.bulk'), [
            'ids' => [$member->id],
            'bulk' => 'suspend',
            'duration' => '1w',
            'reason' => 'other',
            'note' => '',
        ])
        ->assertRedirect(route('admin.users'))
        ->assertSessionHasErrors('note');

    expect($member->fresh()->status)->toBe('active');
});

test('bulk user operations reject invalid IDs and actions', function () {
    $admin = makeBulkUserAdmin();
    $member = makeBulkUserMember();

    $this->actingAs($admin)
        ->withSession(['admin_verified_for' => $admin->id])
        ->from(route('admin.users'))
        ->post(route('admin.users.bulk'), [
            'ids' => [999999],
            'bulk' => 'delete',
        ])
        ->assertRedirect(route('admin.users'))
        ->assertSessionHasErrors(['ids.0', 'bulk']);

    expect($member->fresh()->status)->toBe('active');
});
