<?php

use App\Models\Project;
use App\Models\Review;
use App\Models\Talent;

// Public talent reads + the two public write actions (review, enquiry).

it('shows a published talent profile with blocks and comp card, and 404s an unpublished one', function () {
    $live = Talent::factory()->create(['is_published' => true, 'status' => 'live']);
    $draft = Talent::factory()->create(['is_published' => false, 'status' => 'draft']);

    $this->getJson("/api/v1/talents/{$live->slug}")
        ->assertOk()
        ->assertJsonPath('data.slug', $live->slug)
        ->assertJsonStructure(['data' => ['blocks', 'comp_card', 'services', 'reviews', 'talent_types']]);

    $this->getJson("/api/v1/talents/{$draft->slug}")->assertNotFound();
});

it('returns translatable profile fields in the Accept-Language locale', function () {
    $talent = Talent::factory()->create(['is_published' => true, 'status' => 'live']);
    $talent->setTranslation('headline', 'en', 'Photographer')->setTranslation('headline', 'ar', 'مصوّر')->save();

    $this->withHeaders(['Accept-Language' => 'ar'])->getJson("/api/v1/talents/{$talent->slug}")
        ->assertOk()->assertJsonPath('data.headline', 'مصوّر');
});

it('shows a published talent’s case study scoped to that talent', function () {
    $talent = Talent::factory()->create(['is_published' => true, 'status' => 'live']);
    $project = Project::factory()->for($talent)->create(['client_name' => 'Nomad']);
    $foreign = Project::factory()->create();

    $this->getJson("/api/v1/talents/{$talent->slug}/projects/{$project->id}")
        ->assertOk()->assertJsonPath('data.client_name', 'Nomad');

    // A project that isn't this talent's 404s.
    $this->getJson("/api/v1/talents/{$talent->slug}/projects/{$foreign->id}")->assertNotFound();
});

it('accepts a public review as pending and validates it', function () {
    $talent = Talent::factory()->create(['is_published' => true, 'status' => 'live']);

    $this->postJson("/api/v1/talents/{$talent->slug}/reviews", ['rating' => 6])
        ->assertStatus(422)->assertJsonValidationErrors(['reviewer_name', 'rating', 'body']);

    $this->postJson("/api/v1/talents/{$talent->slug}/reviews", [
        'reviewer_name' => 'Sara', 'rating' => 5, 'body' => 'Brilliant to work with.',
    ])->assertCreated();

    expect(Review::where('talent_id', $talent->id)->where('is_approved', false)->count())->toBe(1);
});

it('accepts a public booking enquiry and blocks unavailable talents', function () {
    $talent = Talent::factory()->create(['is_published' => true, 'status' => 'live', 'availability_status' => 'available']);

    $this->postJson("/api/v1/talents/{$talent->slug}/enquiries", [
        'contact_name' => 'Acme', 'contact_email' => 'hi@acme.test', 'brief' => 'A summer campaign shoot.',
    ])->assertCreated();

    $this->assertDatabaseHas('deal_enquiries', ['talent_id' => $talent->id, 'contact_email' => 'hi@acme.test']);

    $talent->update(['availability_status' => 'unavailable']);
    $this->postJson("/api/v1/talents/{$talent->slug}/enquiries", [
        'contact_name' => 'Acme', 'contact_email' => 'hi@acme.test', 'brief' => 'Another shoot.',
    ])->assertStatus(422);
});
