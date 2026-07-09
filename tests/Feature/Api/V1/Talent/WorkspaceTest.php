<?php

use App\Models\AgencyAffiliation;
use App\Models\Review;
use App\Models\Service;
use App\Models\Talent;
use App\Models\TalentType;

beforeEach(function () {
    $this->talent = Talent::factory()->create();
    $this->token = $this->talent->createToken('t', ['talent'])->plainTextToken;
});

// --- Professions -----------------------------------------------------------

it('adds, sets primary and removes a profession', function () {
    $type = TalentType::factory()->create();

    api()->withToken($this->token)->postJson('/api/v1/talent/professions', ['talent_type_id' => $type->id, 'is_primary' => true])
        ->assertCreated()->assertJsonPath('data.linked.0.slug', $type->slug);

    api()->withToken($this->token)->patchJson("/api/v1/talent/professions/{$type->id}/primary")->assertOk();
    api()->withToken($this->token)->deleteJson("/api/v1/talent/professions/{$type->id}")->assertOk();

    expect($this->talent->fresh()->talentTypes()->count())->toBe(0);
});

// --- Services --------------------------------------------------------------

it('creates, updates, toggles and deletes a service', function () {
    $id = api()->withToken($this->token)->postJson('/api/v1/talent/services', [
        'name' => ['en' => 'Day rate'], 'price' => 500, 'price_unit' => 'day', 'currency' => 'EGP',
    ])->assertCreated()->assertJsonPath('data.name.en', 'Day rate')->json('data.id');

    api()->withToken($this->token)->patchJson("/api/v1/talent/services/{$id}", [
        'name' => ['en' => 'Half day'], 'price' => 300, 'price_unit' => 'day',
    ])->assertOk()->assertJsonPath('data.name.en', 'Half day');

    api()->withToken($this->token)->patchJson("/api/v1/talent/services/{$id}/toggle")->assertOk();
    api()->withToken($this->token)->deleteJson("/api/v1/talent/services/{$id}")->assertOk();
    $this->assertDatabaseMissing('services', ['id' => $id]);
});

it('validates a service (name + price_unit required)', function () {
    api()->withToken($this->token)->postJson('/api/v1/talent/services', ['price' => 10])
        ->assertStatus(422)->assertJsonValidationErrors(['name.en', 'price_unit']);
});

it('forbids updating another talent’s service', function () {
    $foreign = Service::factory()->create();

    api()->withToken($this->token)->patchJson("/api/v1/talent/services/{$foreign->id}", [
        'name' => ['en' => 'x'], 'price_unit' => 'day',
    ])->assertForbidden();
});

it('paginates the services list', function () {
    Service::factory()->count(20)->for($this->talent)->create();

    api()->withToken($this->token)->getJson('/api/v1/talent/services')
        ->assertOk()->assertJsonPath('meta.pagination.total', 20)->assertJsonPath('meta.pagination.per_page', 15);
});

// --- Availability ----------------------------------------------------------

it('reads and updates availability & travel', function () {
    api()->withToken($this->token)->getJson('/api/v1/talent/availability')->assertOk()
        ->assertJsonStructure(['data' => ['availability_status', 'willing_to_travel', 'rate_tier']]);

    api()->withToken($this->token)->patchJson('/api/v1/talent/availability', [
        'availability_status' => 'booked', 'willing_to_travel' => true, 'travel_regions' => ['MENA'], 'rate_tier' => 'premium',
    ])->assertOk()->assertJsonPath('data.availability_status', 'booked');

    expect($this->talent->fresh()->availability_status->getValue())->toBe('booked');
});

// --- Reviews ---------------------------------------------------------------

it('lists, approves and rejects reviews (own moderation)', function () {
    $review = Review::factory()->pending()->for($this->talent)->create();

    api()->withToken($this->token)->getJson('/api/v1/talent/reviews?status=pending')
        ->assertOk()->assertJsonPath('meta.pagination.total', 1);

    api()->withToken($this->token)->patchJson("/api/v1/talent/reviews/{$review->id}/approve")->assertOk();
    expect($review->fresh()->status->getValue())->toBe('approved');
});

it('forbids moderating another talent’s review', function () {
    $foreign = Review::factory()->pending()->create();

    api()->withToken($this->token)->patchJson("/api/v1/talent/reviews/{$foreign->id}/approve")->assertForbidden();
});

// --- Affiliations & press --------------------------------------------------

it('manages affiliations (create, update, end, delete)', function () {
    $id = api()->withToken($this->token)->postJson('/api/v1/talent/affiliations', [
        'agency_name' => 'Elite', 'representation_type' => 'exclusive',
    ])->assertCreated()->json('data.id');

    api()->withToken($this->token)->patchJson("/api/v1/talent/affiliations/{$id}", [
        'agency_name' => 'Elite MENA', 'representation_type' => 'non_exclusive',
    ])->assertOk()->assertJsonPath('data.agency_name', 'Elite MENA');

    api()->withToken($this->token)->patchJson("/api/v1/talent/affiliations/{$id}/end")->assertOk()->assertJsonPath('data.is_current', false);
    api()->withToken($this->token)->deleteJson("/api/v1/talent/affiliations/{$id}")->assertOk();
});

it('validates and manages press', function () {
    api()->withToken($this->token)->postJson('/api/v1/talent/press', ['title' => 'Only title'])
        ->assertStatus(422)->assertJsonValidationErrors('publication');

    $id = api()->withToken($this->token)->postJson('/api/v1/talent/press', [
        'publication' => 'Vogue', 'title' => 'Cover story', 'url' => 'https://vogue.com/x',
    ])->assertCreated()->json('data.id');

    api()->withToken($this->token)->deleteJson("/api/v1/talent/press/{$id}")->assertOk();
});

it('forbids ending another talent’s affiliation', function () {
    $foreign = AgencyAffiliation::factory()->create();

    api()->withToken($this->token)->patchJson("/api/v1/talent/affiliations/{$foreign->id}/end")->assertForbidden();
});

// --- Comp card -------------------------------------------------------------

it('upserts, reads and deletes the comp card', function () {
    api()->withToken($this->token)->getJson('/api/v1/talent/comp-card')->assertOk()->assertJsonPath('data', null);

    api()->withToken($this->token)->putJson('/api/v1/talent/comp-card', [
        'height_cm' => 178, 'eye_color' => 'brown', 'measurements' => ['chest' => 90],
    ])->assertOk()->assertJsonPath('data.height_cm', 178)->assertJsonPath('data.eye_color', 'brown');

    api()->withToken($this->token)->getJson('/api/v1/talent/comp-card')->assertOk()->assertJsonPath('data.height_cm', 178);

    api()->withToken($this->token)->deleteJson('/api/v1/talent/comp-card')->assertOk();
    $this->assertDatabaseMissing('comp_cards', ['talent_id' => $this->talent->id]);
});

it('validates the comp card', function () {
    api()->withToken($this->token)->putJson('/api/v1/talent/comp-card', ['height_cm' => 9999])
        ->assertStatus(422)->assertJsonValidationErrors('height_cm');
});

// --- Account ---------------------------------------------------------------

it('reads, updates and publishes the account', function () {
    // Start from a draft profile so publish is a valid transition.
    $this->talent->update(['is_published' => false, 'status' => 'draft']);

    api()->withToken($this->token)->getJson('/api/v1/talent/account')->assertOk()
        ->assertJsonStructure(['data' => ['slug', 'is_published', 'status']]);

    api()->withToken($this->token)->patchJson('/api/v1/talent/account', ['slug' => 'amgad-emad', 'meta' => ['x' => 1]])
        ->assertOk()->assertJsonPath('data.slug', 'amgad-emad');

    api()->withToken($this->token)->patchJson('/api/v1/talent/account/publish', ['publish' => true])
        ->assertOk()->assertJsonPath('data.is_published', true);
});

it('rejects a duplicate slug but allows keeping own slug', function () {
    Talent::factory()->create(['slug' => 'taken-slug']);

    api()->withToken($this->token)->patchJson('/api/v1/talent/account', ['slug' => 'taken-slug'])
        ->assertStatus(422)->assertJsonValidationErrors('slug');

    // Re-submitting the talent's own slug must not collide with itself.
    api()->withToken($this->token)->patchJson('/api/v1/talent/account', ['slug' => $this->talent->slug])->assertOk();
});
