
<?php

use App\Models\Exchange;
use App\Models\User;

test('pending exchange can move to ongoing', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $provider = User::factory()->create();
    $learner = User::factory()->create();

    $exchange = Exchange::create([
        'provider_id' => $provider->id,
        'learner_id' => $learner->id,
        'skill_offered' => 'Programming',
        'skill_wanted' => 'Design',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($admin)->patch(
        route('admin.exchanges.update', $exchange),
        ['status' => 'ongoing']
    );

    $response->assertRedirect(route('admin.exchanges'));

    $this->assertDatabaseHas('exchanges', [
        'id' => $exchange->id,
        'status' => 'ongoing',
    ]);
});

test('pending exchange cannot skip directly to completed', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $provider = User::factory()->create();
    $learner = User::factory()->create();

    $exchange = Exchange::create([
        'provider_id' => $provider->id,
        'learner_id' => $learner->id,
        'skill_offered' => 'Programming',
        'skill_wanted' => 'Design',
        'status' => 'pending',
    ]);

    $response = $this
        ->actingAs($admin)
        ->from(route('admin.exchanges.show', $exchange))
        ->patch(route('admin.exchanges.update', $exchange), [
            'status' => 'completed',
        ]);

    $response
        ->assertRedirect(route('admin.exchanges.show', $exchange))
        ->assertSessionHasErrors('status');

    $this->assertDatabaseHas('exchanges', [
        'id' => $exchange->id,
        'status' => 'pending',
    ]);
});

test('completed exchange cannot be reopened', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $provider = User::factory()->create();
    $learner = User::factory()->create();

    $exchange = Exchange::create([
        'provider_id' => $provider->id,
        'learner_id' => $learner->id,
        'skill_offered' => 'Programming',
        'skill_wanted' => 'Design',
        'status' => 'completed',
    ]);

    $response = $this
        ->actingAs($admin)
        ->from(route('admin.exchanges.show', $exchange))
        ->patch(route('admin.exchanges.update', $exchange), [
            'status' => 'pending',
        ]);

    $response
        ->assertRedirect(route('admin.exchanges.show', $exchange))
        ->assertSessionHasErrors('status');

    $this->assertDatabaseHas('exchanges', [
        'id' => $exchange->id,
        'status' => 'completed',
    ]);
});


test('exchange status transitions follow the permitted workflow', function (
    string $currentStatus,
    string $newStatus,
    bool $allowed
) {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $provider = User::factory()->create();
    $learner = User::factory()->create();

    $exchange = Exchange::create([
        'provider_id' => $provider->id,
        'learner_id' => $learner->id,
        'skill_offered' => 'Programming',
        'skill_wanted' => 'Design',
        'status' => $currentStatus,
    ]);

    $response = $this
        ->actingAs($admin)
        ->from(route('admin.exchanges.show', $exchange))
        ->patch(route('admin.exchanges.update', $exchange), [
            'status' => $newStatus,
        ]);

    if ($allowed) {
        $response->assertRedirect(route('admin.exchanges'));

        $this->assertDatabaseHas('exchanges', [
            'id' => $exchange->id,
            'status' => $newStatus,
        ]);
    } else {
        $response
            ->assertRedirect(route('admin.exchanges.show', $exchange))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('exchanges', [
            'id' => $exchange->id,
            'status' => $currentStatus,
        ]);
    }
})->with([
    'pending can become disputed' => ['pending', 'disputed', true],
    'ongoing can become completed' => ['ongoing', 'completed', true],
    'ongoing can become disputed' => ['ongoing', 'disputed', true],
    'disputed can return to ongoing' => ['disputed', 'ongoing', true],
    'disputed can become completed' => ['disputed', 'completed', true],
    'ongoing cannot return to pending' => ['ongoing', 'pending', false],
    'disputed cannot return to pending' => ['disputed', 'pending', false],
    'completed cannot return to ongoing' => ['completed', 'ongoing', false],
    'completed cannot become disputed' => ['completed', 'disputed', false],
]);
