<?php

use App\Models\Review;
use App\Models\Service;
use App\Models\Talent;

it('creates a service, paginates the list, toggles and removes', function () {
    $talent = Talent::factory()->create();

    $id = $this->actingAs($talent, 'talent')
        ->postJson(route('talent.services.store'), ['name' => ['en' => 'Shoot'], 'price' => 1000, 'currency' => 'EGP', 'price_unit' => 'day'])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($talent, 'talent')->getJson(route('talent.services.data'))
        ->assertOk()
        ->assertJsonPath('meta.pagination.total', 1);

    $this->actingAs($talent, 'talent')->patchJson(route('talent.services.toggle', $id))->assertOk();
    expect(Service::find($id)->is_active)->toBeFalse();

    $this->actingAs($talent, 'talent')->deleteJson(route('talent.services.destroy', $id))->assertOk();
    expect(Service::find($id))->toBeNull();
});

it('validates a service (missing name → 422 envelope)', function () {
    $talent = Talent::factory()->create();

    $response = $this->actingAs($talent, 'talent')
        ->postJson(route('talent.services.store'), ['price_unit' => 'day'])
        ->assertStatus(422)
        ->assertJsonPath('success', false);

    expect($response->json('errors'))->toHaveKey('name.en');
});

it('forbids managing another talent’s service', function () {
    $owner = Talent::factory()->create();
    $service = Service::factory()->for($owner)->create();
    $intruder = Talent::factory()->create();

    $this->actingAs($intruder, 'talent')->patchJson(route('talent.services.toggle', $service->id))->assertForbidden();
    $this->actingAs($intruder, 'talent')->deleteJson(route('talent.services.destroy', $service->id))->assertForbidden();
});

it('moderates reviews and forbids foreign moderation', function () {
    $talent = Talent::factory()->create();
    $review = Review::factory()->pending()->for($talent)->create();

    $this->actingAs($talent, 'talent')->patchJson(route('talent.reviews.approve', $review->id))->assertOk();
    expect($review->fresh()->is_approved)->toBeTrue();

    $this->actingAs($talent, 'talent')->getJson(route('talent.reviews.data', ['status' => 'approved']))
        ->assertOk()
        ->assertJsonPath('meta.pagination.total', 1);

    $intruder = Talent::factory()->create();
    $foreign = Review::factory()->pending()->for($talent)->create();
    $this->actingAs($intruder, 'talent')->patchJson(route('talent.reviews.approve', $foreign->id))->assertForbidden();
});
