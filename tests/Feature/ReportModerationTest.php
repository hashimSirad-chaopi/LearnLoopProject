
<?php

use App\Models\Report;
use App\Models\User;

test('pending report can be resolved and logged', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $report = Report::create([
        'title' => 'Inappropriate listing',
        'reason' => 'The listing contains misleading information.',
        'status' => 'pending',
    ]);

    $response = $this
        ->actingAs($admin)
        ->from(route('admin.reports'))
        ->patch(route('admin.reports.resolve', $report));

    $response->assertRedirect(route('admin.reports'));

    $this->assertDatabaseHas('reports', [
        'id' => $report->id,
        'status' => 'resolved',
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'action' => 'report.resolved',
        'description' => 'Resolved report "Inappropriate listing"',
    ]);
});

test('resolved report cannot be dismissed', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $report = Report::create([
        'title' => 'Resolved report',
        'reason' => 'Already reviewed.',
        'status' => 'resolved',
    ]);

    $response = $this
        ->actingAs($admin)
        ->from(route('admin.reports'))
        ->patch(route('admin.reports.dismiss', $report));

    $response
        ->assertRedirect(route('admin.reports'))
        ->assertSessionHasErrors('report');

    $this->assertDatabaseHas('reports', [
        'id' => $report->id,
        'status' => 'resolved',
    ]);

    $this->assertDatabaseMissing('activity_logs', [
        'action' => 'report.dismissed',
        'description' => 'Dismissed report "Resolved report"',
    ]);
});

test('dismissed report cannot be resolved', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $report = Report::create([
        'title' => 'Dismissed report',
        'reason' => 'Reviewed and dismissed.',
        'status' => 'dismissed',
    ]);

    $response = $this
        ->actingAs($admin)
        ->from(route('admin.reports'))
        ->patch(route('admin.reports.resolve', $report));

    $response
        ->assertRedirect(route('admin.reports'))
        ->assertSessionHasErrors('report');

    $this->assertDatabaseHas('reports', [
        'id' => $report->id,
        'status' => 'dismissed',
    ]);
});
