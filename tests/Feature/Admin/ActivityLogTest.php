<?php

use App\Models\ContractFlow;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

it('records subject, causer and old/new properties when an admin edits a contract flow', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin, 'admin');

    $flow = ContractFlow::factory()->create(['name' => 'Original']);
    $flow->update(['name' => 'Renamed']);

    $activity = Activity::inLog('contract_flow')->latest('id')->first();

    expect($activity)->not->toBeNull();
    expect($activity->description)->toBe('updated');

    // Subject — the thing changed.
    expect($activity->subject_type)->toBe(ContractFlow::class);
    expect((int) $activity->subject_id)->toBe($flow->id);

    // Causer — who changed it (resolved from the admin guard).
    expect($activity->causer_type)->toBe(User::class);
    expect((int) $activity->causer_id)->toBe($admin->id);

    // Changed attributes — old + new values (this activitylog version stores model
    // changes under `attribute_changes`; `properties` holds ad-hoc custom data).
    expect(data_get($activity->attribute_changes, 'attributes.name'))->toBe('Renamed');
    expect(data_get($activity->attribute_changes, 'old.name'))->toBe('Original');
});

it('only logs dirty attributes on update', function () {
    $flow = ContractFlow::factory()->create(['name' => 'Keep', 'is_active' => true]);
    $flow->update(['is_active' => false]);

    $activity = Activity::inLog('contract_flow')->latest('id')->first();

    expect(data_get($activity->attribute_changes, 'attributes'))->toHaveKey('is_active');
    expect(data_get($activity->attribute_changes, 'attributes'))->not->toHaveKey('name');
});
