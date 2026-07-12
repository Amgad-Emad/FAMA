<?php

use App\Models\DealEnquiry;
use App\Models\Talent;

beforeEach(fn () => $this->withoutVite());

it('renders the booking form and stores a new enquiry', function () {
    $talent = Talent::factory()->create(['slug' => 'jane']);

    $this->get(route('talent.enquire', ['slug' => 'jane']))->assertOk();

    $this->postJson(route('talent.enquire.store', ['slug' => 'jane']), [
        'contact_name' => 'Acme', 'contact_email' => 'hi@acme.com', 'brief' => 'We need a campaign shoot.',
    ])->assertCreated()->assertJsonPath('success', true);

    expect(DealEnquiry::where('talent_id', $talent->id)->where('status', 'new')->count())->toBe(1);
});

it('validates the enquiry (missing name & brief → 422)', function () {
    Talent::factory()->create(['slug' => 'jane']);

    $this->postJson(route('talent.enquire.store', ['slug' => 'jane']), ['contact_email' => 'hi@acme.com'])
        ->assertStatus(422)->assertJsonPath('success', false);
});
