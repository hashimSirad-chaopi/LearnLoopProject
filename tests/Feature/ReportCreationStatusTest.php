
<?php

use App\Models\Report;
use App\Models\User;

test('new report starts pending even if another status is submitted', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $reporter = User::factory()->create();
    $reportedUser = User::factory()->create();

    $response = $this
        ->actingAs($admin)
        ->post(route('admin.reports.store'), [
            'title' => 'Misleading listing',
            'reported_by' => $reporter->id,
            'reported_user_id' => $reportedUser->id,
            'listing_id' => null,
            'reason' => 'The listing contains misleading information.',
            'status' => 'resolved',
        ]);

    $response->assertRedirect(route('admin.reports'));

    $this->assertDatabaseHas('reports', [
        'title' => 'Misleading listing',
        'reported_by' => $reporter->id,
        'reported_user_id' => $reportedUser->id,
        'status' => 'pending',
    ]);
});


test('report cannot target the same user who filed it', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $reporter = User::factory()->create();

    $response = $this
        ->actingAs($admin)
        ->from(route('admin.reports.create'))
        ->post(route('admin.reports.store'), [
            'title' => 'Self-report attempt',
            'reported_by' => $reporter->id,
            'reported_user_id' => $reporter->id,
            'listing_id' => null,
            'reason' => 'Attempting to report the same account.',
        ]);

    $response
        ->assertRedirect(route('admin.reports.create'))
        ->assertSessionHasErrors('reported_user_id');

    $this->assertDatabaseMissing('reports', [
        'title' => 'Self-report attempt',
    ]);
});
