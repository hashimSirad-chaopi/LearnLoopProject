<?php

use App\Models\Report;
use App\Models\User;

test('report search respects the selected status filter', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $resolvedTitleMatch = Report::create([
        'title' => 'Target seller issue',
        'reason' => 'Unrelated detail',
        'status' => 'resolved',
    ]);

    $pendingReasonMatch = Report::create([
        'title' => 'Unrelated title',
        'reason' => 'Target scam report',
        'status' => 'pending',
    ]);

    $pendingTitleMatch = Report::create([
        'title' => 'Target inappropriate listing',
        'reason' => 'Other detail',
        'status' => 'pending',
    ]);

    $response = $this
        ->actingAs($admin)
        ->get(route('admin.reports', [
            'search' => 'Target',
            'status' => 'pending',
        ]));

    $response->assertOk();

    $response->assertViewHas('reports', function ($reports) use (
        $resolvedTitleMatch,
        $pendingReasonMatch,
        $pendingTitleMatch
    ) {
        return $reports->count() === 2
            && $reports->contains('id', $pendingReasonMatch->id)
            && $reports->contains('id', $pendingTitleMatch->id)
            && ! $reports->contains('id', $resolvedTitleMatch->id);
    });
});