
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