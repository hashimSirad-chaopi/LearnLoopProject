
<?php

use App\Models\Exchange;
use App\Models\User;

test('new exchange starts pending even if another status is submitted', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $provider = User::factory()->create();
    $learner = User::factory()->create();

    $response = $this
        ->actingAs($admin)
        ->post(route('admin.exchanges.store'), [
            'provider_id' => $provider->id,
            'learner_id' => $learner->id,
            'skill_offered' => 'Python Programming',
            'skill_wanted' => 'Graphic Design',
            'status' => 'completed',
        ]);

    $response->assertRedirect(route('admin.exchanges'));

    $this->assertDatabaseHas('exchanges', [
        'provider_id' => $provider->id,
        'learner_id' => $learner->id,
        'skill_offered' => 'Python Programming',
        'skill_wanted' => 'Graphic Design',
        'status' => 'pending',
    ]);
});


test('exchange cannot be created with a suspended provider', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $provider = User::factory()->create([
        'status' => 'suspended',
    ]);

    $learner = User::factory()->create([
        'status' => 'active',
    ]);

    $response = $this
        ->actingAs($admin)
        ->from(route('admin.exchanges.create'))
        ->post(route('admin.exchanges.store'), [
            'provider_id' => $provider->id,
            'learner_id' => $learner->id,
            'skill_offered' => 'Python',
            'skill_wanted' => 'Graphic Design',
        ]);

    $response
        ->assertRedirect(route('admin.exchanges.create'))
        ->assertSessionHasErrors('provider_id');

    $this->assertDatabaseMissing('exchanges', [
        'provider_id' => $provider->id,
        'learner_id' => $learner->id,
    ]);
});

test('exchange cannot be created with a suspended learner', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $provider = User::factory()->create([
        'status' => 'active',
    ]);

    $learner = User::factory()->create([
        'status' => 'suspended',
    ]);

    $response = $this
        ->actingAs($admin)
        ->from(route('admin.exchanges.create'))
        ->post(route('admin.exchanges.store'), [
            'provider_id' => $provider->id,
            'learner_id' => $learner->id,
            'skill_offered' => 'Python',
            'skill_wanted' => 'Graphic Design',
        ]);

    $response
        ->assertRedirect(route('admin.exchanges.create'))
        ->assertSessionHasErrors('learner_id');

    $this->assertDatabaseMissing('exchanges', [
        'provider_id' => $provider->id,
        'learner_id' => $learner->id,
    ]);
});
