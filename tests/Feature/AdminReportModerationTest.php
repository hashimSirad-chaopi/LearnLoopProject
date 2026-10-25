<?php

use App\Models\Report;
use App\Models\User;

function makeReportModerationAdmin(): User
{
    return User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);
}

function makeReportModerationMember(): User
{
    return User::factory()->create([
        'role' => 'user',
        'status' => 'active',
    ]);
}

function makePendingModerationReport(User $reporter, User $reported): Report
{
    return Report::create([
        'title' => 'Community rules concern',
        'reported_by' => $reporter->id,
        'reported_user_id' => $reported->id,
        'reason' => 'The reported content may break community rules.',
        'status' => 'pending',
    ]);
}

test('resolving a report with a warning records the action and counts it as upheld', function () {
    $admin = makeReportModerationAdmin();
    $reporter = makeReportModerationMember();
    $reported = makeReportModerationMember();
    $report = makePendingModerationReport($reporter, $reported);

    $this->actingAs($admin)
        ->withSession(['admin_verified_for' => $admin->id])
        ->from(route('admin.reports'))
        ->patch(route('admin.reports.resolve', $report), [
            'action' => 'warn',
            'note' => 'Please follow the community rules.',
        ])
        ->assertRedirect(route('admin.reports'));

    $this->assertDatabaseHas('reports', [
        'id' => $report->id,
        'status' => 'resolved',
        'action_taken' => 'warn',
        'admin_note' => 'Please follow the community rules.',
    ]);

    expect(Report::upheldAgainst($reported->id))->toBe(1);
    expect(Report::warningsAgainst($reported->id))->toBe(1);
    expect($reported->fresh()->status)->toBe('active');
});

test('dismissing a report does not count it as upheld', function () {
    $admin = makeReportModerationAdmin();
    $reporter = makeReportModerationMember();
    $reported = makeReportModerationMember();
    $report = makePendingModerationReport($reporter, $reported);

    $this->actingAs($admin)
        ->withSession(['admin_verified_for' => $admin->id])
        ->from(route('admin.reports'))
        ->patch(route('admin.reports.dismiss', $report))
        ->assertRedirect(route('admin.reports'));

    $this->assertDatabaseHas('reports', [
        'id' => $report->id,
        'status' => 'dismissed',
    ]);

    expect(Report::upheldAgainst($reported->id))->toBe(0);
    expect($reported->fresh()->status)->toBe('active');
});

test('reaching the upheld report threshold automatically suspends a member', function () {
    config(['learnloop.auto_suspend_after' => 3]);

    $admin = makeReportModerationAdmin();
    $reporter = makeReportModerationMember();
    $reported = makeReportModerationMember();

    foreach (range(1, 2) as $number) {
        Report::create([
            'title' => "Earlier upheld report {$number}",
            'reported_by' => $reporter->id,
            'reported_user_id' => $reported->id,
            'reason' => 'Earlier violation.',
            'status' => 'resolved',
            'action_taken' => 'none',
            'resolved_at' => now(),
        ]);
    }

    $pending = makePendingModerationReport($reporter, $reported);

    $this->actingAs($admin)
        ->withSession(['admin_verified_for' => $admin->id])
        ->from(route('admin.reports'))
        ->patch(route('admin.reports.resolve', $pending), [
            'action' => 'none',
        ])
        ->assertRedirect(route('admin.reports'));

    expect(Report::upheldAgainst($reported->id))->toBe(3);

    $reported->refresh();

    expect($reported->status)->toBe('suspended');
    expect($reported->suspension_reason)->toBe('reports');
});

test('a report cannot suspend an administrator', function () {
    $admin = makeReportModerationAdmin();
    $reporter = makeReportModerationMember();
    $reportedAdmin = makeReportModerationAdmin();
    $report = makePendingModerationReport($reporter, $reportedAdmin);

    $this->actingAs($admin)
        ->withSession(['admin_verified_for' => $admin->id])
        ->from(route('admin.reports'))
        ->patch(route('admin.reports.resolve', $report), [
            'action' => 'suspend',
            'reason' => 'harassment',
            'duration' => '1d',
        ])
        ->assertRedirect(route('admin.reports'));

    $this->assertDatabaseHas('reports', [
        'id' => $report->id,
        'status' => 'resolved',
        'action_taken' => 'none',
    ]);

    expect($reportedAdmin->fresh()->status)->toBe('active');
});

test('a report that was already reviewed cannot be resolved again', function () {
    $admin = makeReportModerationAdmin();
    $reporter = makeReportModerationMember();
    $reported = makeReportModerationMember();

    $report = Report::create([
        'title' => 'Already dismissed report',
        'reported_by' => $reporter->id,
        'reported_user_id' => $reported->id,
        'reason' => 'Previously reviewed.',
        'status' => 'dismissed',
        'resolved_at' => now(),
    ]);

    $this->actingAs($admin)
        ->withSession(['admin_verified_for' => $admin->id])
        ->from(route('admin.reports'))
        ->patch(route('admin.reports.resolve', $report), [
            'action' => 'suspend',
            'reason' => 'harassment',
            'duration' => '1d',
        ])
        ->assertRedirect(route('admin.reports'))
        ->assertSessionHasErrors('report');

    expect($report->fresh()->status)->toBe('dismissed');
    expect($reported->fresh()->status)->toBe('active');
});
