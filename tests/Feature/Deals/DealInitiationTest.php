<?php

use App\Models\Brand;
use App\Models\Campaign;
use App\Models\Deal;
use App\Models\DealEnquiry;
use App\Models\DealFlow;
use App\Models\Service;
use App\Models\Talent;
use App\Models\TalentType;
use App\Notifications\DealStarted;
use App\Services\BrandReviewService;

/** The standard flow as the resolvable GLOBAL active default. */
function stdDefaultFlow(): DealFlow
{
    return DealFlow::factory()->standard()->default()->create(['applies_to' => null]);
}

/** A published, bookable talent. */
function bookableTalent(): Talent
{
    return Talent::factory()->create(['is_published' => true, 'status' => 'live', 'availability_status' => 'available']);
}

function brandTokenFor(Brand $brand): string
{
    return $brand->createToken('t', ['brand'])->plainTextToken;
}

// ---------------------------------------------------------------------------
// PATH A — brand-initiated deal.
// ---------------------------------------------------------------------------

it('starts a deal via the API — snapshots steps, activates the first, sets status, notifies the talent', function () {
    stdDefaultFlow();
    $brand = Brand::factory()->create();
    $talent = bookableTalent();

    $response = api()->withToken(brandTokenFor($brand))
        ->postJson('/api/v1/brand/deals', ['talent_id' => $talent->id, 'brief' => 'Summer campaign'])
        ->assertCreated();

    $response->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'awaiting_brand') // standard flow's first step (brief) is a brand step
        ->assertJsonStructure(['data' => ['id', 'reference', 'status', 'current_step'], 'meta' => ['room']]);

    $dealId = $response->json('data.id');
    $deal = Deal::findOrFail($dealId);
    expect($deal->steps()->count())->toBe(7)          // full standard flow snapshotted
        ->and($deal->currentStep->key)->toBe('brief')
        ->and($deal->initiated_by)->toBe('brand')
        ->and($deal->brand_id)->toBe($brand->id)
        ->and($deal->talent_id)->toBe($talent->id);

    // The talent sees the new deal in their inbox immediately + is notified.
    api()->withToken($talent->createToken('t', ['talent'])->plainTextToken)
        ->getJson('/api/v1/talent/deals')->assertOk()->assertJsonPath('meta.pagination.total', 1);
    expect($talent->notifications()->where('type', DealStarted::class)->count())->toBe(1);
});

it('starts a deal via the web dashboard (Ajax → redirect)', function () {
    stdDefaultFlow();
    $brand = Brand::factory()->create();
    $talent = bookableTalent();

    $this->actingAs($brand, 'brand')
        ->postJson(route('brand.deals.store'), ['talent_id' => $talent->id])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['id', 'reference', 'redirect']]);

    expect(Deal::where('brand_id', $brand->id)->where('talent_id', $talent->id)->exists())->toBeTrue();
});

it('accepts an optional service that belongs to the talent and rejects a foreign one', function () {
    stdDefaultFlow();
    $brand = Brand::factory()->create();
    $talent = bookableTalent();
    $ownService = Service::factory()->for($talent)->create();
    $foreignService = Service::factory()->create();

    api()->withToken(brandTokenFor($brand))->postJson('/api/v1/brand/deals', [
        'talent_id' => $talent->id, 'service_id' => $ownService->id,
    ])->assertCreated();

    api()->withToken(brandTokenFor($brand))->postJson('/api/v1/brand/deals', [
        'talent_id' => $talent->id, 'service_id' => $foreignService->id,
    ])->assertStatus(422)->assertJsonValidationErrors('service_id');
});

// ---------------------------------------------------------------------------
// Guards (422).
// ---------------------------------------------------------------------------

it('refuses to start against a booked / unavailable / unpublished talent, or from an incomplete brand', function () {
    stdDefaultFlow();
    $brand = Brand::factory()->create();

    $booked = Talent::factory()->create(['is_published' => true, 'status' => 'live', 'availability_status' => 'booked']);
    $unavailable = Talent::factory()->create(['is_published' => true, 'status' => 'live', 'availability_status' => 'unavailable']);
    $unpublished = Talent::factory()->create(['is_published' => false, 'status' => 'draft', 'availability_status' => 'available']);

    foreach ([$booked, $unavailable, $unpublished] as $talent) {
        api()->withToken(brandTokenFor($brand))->postJson('/api/v1/brand/deals', ['talent_id' => $talent->id])
            ->assertStatus(422);
    }

    // An incomplete brand cannot start a deal even against a bookable talent.
    $incompleteBrand = Brand::factory()->incomplete()->create();
    api()->withToken(brandTokenFor($incompleteBrand))->postJson('/api/v1/brand/deals', ['talent_id' => bookableTalent()->id])
        ->assertStatus(422);
});

// ---------------------------------------------------------------------------
// Flow resolution.
// ---------------------------------------------------------------------------

it('prefers the category-scoped default flow over the global default', function () {
    $global = stdDefaultFlow(); // applies_to = null
    $modelFlow = DealFlow::factory()->standard()->default()->create(['applies_to' => 'model']);

    $brand = Brand::factory()->create();
    $talent = bookableTalent();
    $type = TalentType::factory()->create(['category' => 'model']);
    $talent->talentTypes()->attach($type->id, ['is_primary' => true, 'position' => 0]);

    $id = api()->withToken(brandTokenFor($brand))->postJson('/api/v1/brand/deals', ['talent_id' => $talent->id])
        ->assertCreated()->json('data.id');

    expect(Deal::find($id)->deal_flow_id)->toBe($modelFlow->id)->not->toBe($global->id);
});

it('422s when no active default flow exists (admin must publish one)', function () {
    // An active NON-default flow exists, but no default → unresolved.
    DealFlow::factory()->standard()->create(['is_default' => false, 'applies_to' => null]);
    $brand = Brand::factory()->create();

    api()->withToken(brandTokenFor($brand))->postJson('/api/v1/brand/deals', ['talent_id' => bookableTalent()->id])
        ->assertStatus(422);
});

// ---------------------------------------------------------------------------
// Campaign link.
// ---------------------------------------------------------------------------

it('links the deal to a campaign that belongs to the brand', function () {
    stdDefaultFlow();
    $brand = Brand::factory()->create();
    $talent = bookableTalent();
    $campaign = Campaign::factory()->for($brand)->create();
    $foreignCampaign = Campaign::factory()->create();

    $id = api()->withToken(brandTokenFor($brand))->postJson('/api/v1/brand/deals', [
        'talent_id' => $talent->id, 'campaign_id' => $campaign->id,
    ])->assertCreated()->json('data.id');

    expect(Deal::find($id)->campaign_id)->toBe($campaign->id);
    expect($campaign->deals()->whereKey($id)->exists())->toBeTrue();

    // A campaign owned by another brand is rejected.
    api()->withToken(brandTokenFor($brand))->postJson('/api/v1/brand/deals', [
        'talent_id' => $talent->id, 'campaign_id' => $foreignCampaign->id,
    ])->assertStatus(422)->assertJsonValidationErrors('campaign_id');
});

// ---------------------------------------------------------------------------
// PATH B — enquiry conversion.
// ---------------------------------------------------------------------------

it('lists and converts an email-matched pending enquiry (API)', function () {
    stdDefaultFlow();
    $brand = Brand::factory()->create(['email' => 'hi@nomad.test']);
    $talent = bookableTalent();
    $enquiry = DealEnquiry::factory()->for($talent)->create(['contact_email' => 'hi@nomad.test', 'status' => 'new']);

    api()->withToken(brandTokenFor($brand))->getJson('/api/v1/brand/enquiries')
        ->assertOk()->assertJsonPath('meta.pagination.total', 1)->assertJsonPath('data.0.id', $enquiry->id);

    $response = api()->withToken(brandTokenFor($brand))
        ->postJson("/api/v1/brand/enquiries/{$enquiry->id}/convert")->assertCreated();

    $dealId = $response->json('data.id');
    expect($enquiry->fresh()->status)->toBe('converted')
        ->and($enquiry->fresh()->converted_deal_id)->toBe($dealId);

    // The deal room is reachable.
    api()->withToken(brandTokenFor($brand))->getJson("/api/v1/brand/deals/{$dealId}")->assertOk();
});

it('403s converting an enquiry not addressed to the brand', function () {
    stdDefaultFlow();
    $brand = Brand::factory()->create(['email' => 'me@brand.test']);
    $enquiry = DealEnquiry::factory()->for(bookableTalent())->create(['contact_email' => 'someone@else.test', 'status' => 'new']);

    api()->withToken(brandTokenFor($brand))->postJson("/api/v1/brand/enquiries/{$enquiry->id}/convert")
        ->assertForbidden();
});

it('422s converting an already-handled enquiry', function () {
    stdDefaultFlow();
    $brand = Brand::factory()->create(['email' => 'me@brand.test']);
    $enquiry = DealEnquiry::factory()->for(bookableTalent())->create(['contact_email' => 'me@brand.test', 'status' => 'converted']);

    api()->withToken(brandTokenFor($brand))->postJson("/api/v1/brand/enquiries/{$enquiry->id}/convert")
        ->assertStatus(422);
});

// ---------------------------------------------------------------------------
// END-TO-END — a UI-initiated deal walks the whole loop to completion.
// ---------------------------------------------------------------------------

it('lists and converts a pending enquiry via the web dashboard', function () {
    stdDefaultFlow();
    $brand = Brand::factory()->create(['email' => 'hi@nomad.test']);
    $enquiry = DealEnquiry::factory()->for(bookableTalent())->create(['contact_email' => 'hi@nomad.test', 'status' => 'new']);

    $this->actingAs($brand, 'brand')->getJson(route('brand.enquiries.data'))
        ->assertOk()->assertJsonPath('meta.pagination.total', 1);

    $this->actingAs($brand, 'brand')->postJson(route('brand.enquiries.convert', $enquiry))
        ->assertCreated()->assertJsonStructure(['data' => ['id', 'redirect']]);

    expect($enquiry->fresh()->status)->toBe('converted');

    // A foreign enquiry is forbidden on the web too.
    $foreign = DealEnquiry::factory()->for(bookableTalent())->create(['contact_email' => 'other@x.test', 'status' => 'new']);
    $this->actingAs($brand, 'brand')->postJson(route('brand.enquiries.convert', $foreign))->assertForbidden();
});

it('shows the "Start a deal" CTA to an onboarded brand on discover + the talent profile, and hides it from guests', function () {
    $brand = Brand::factory()->create();
    $talent = bookableTalent();

    // A guest sees no brand CTA on the public profile (asserted before authenticating,
    // since actingAs persists across a test's requests).
    $this->get('/'.$talent->slug)->assertOk()->assertDontSee(__('Start a deal'));

    // Discovery feed (brand dashboard) + the profile, as the onboarded brand.
    $this->actingAs($brand, 'brand')->get(route('brand.discover'))->assertOk()->assertSee(__('Start a deal'));
    $this->actingAs($brand, 'brand')->get('/'.$talent->slug)->assertOk()->assertSee(__('Start a deal'));
});

it('runs the full loop from a UI-initiated deal: initiate → advance every step → complete → credibility + review window', function () {
    stdDefaultFlow();
    $brand = Brand::factory()->create();
    $talent = bookableTalent();
    $brandToken = brandTokenFor($brand);
    $talentToken = $talent->createToken('t', ['talent'])->plainTextToken;

    // Initiate through the real entry point.
    $dealId = api()->withToken($brandToken)->postJson('/api/v1/brand/deals', ['talent_id' => $talent->id])
        ->assertCreated()->json('data.id');
    $room = "/api/v1/brand/deals/{$dealId}";

    // Walk every step as the correct actor, via the API.
    api()->withToken($brandToken)->postJson("{$room}/advance", ['fields' => ['scope' => 'a', 'dates' => 'b', 'budget' => 'c']])->assertOk(); // brief (brand)
    api()->withToken($talentToken)->postJson("/api/v1/talent/deals/{$dealId}/advance", ['fields' => ['amount' => 4000]])->assertOk();          // quote (talent)
    api()->withToken($brandToken)->postJson("{$room}/advance", ['note' => 'ok'])->assertOk();                                                   // agreement (brand)
    api()->withToken($brandToken)->postJson("{$room}/skip")->assertOk();                                                                        // payment (brand, skippable)
    api()->withToken($talentToken)->postJson("/api/v1/talent/deals/{$dealId}/advance", ['attachments' => ['final.zip']])->assertOk();           // delivery (talent)
    api()->withToken($brandToken)->postJson("{$room}/advance", ['note' => 'great'])->assertOk();                                                // sign-off (brand) → complete

    $deal = Deal::findOrFail($dealId);
    expect($deal->status->getValue())->toBe('completed')
        ->and($deal->current_step_id)->toBeNull();

    // Brand credibility accrued (AccrueBrandCredibility listener on DealCompleted).
    expect($brand->credibility()->first()->completed_projects_count)->toBe(1);

    // The talent's brand-review window is open (submit only succeeds once completed).
    $review = app(BrandReviewService::class)->submit($deal, [
        'communication_rating' => 5, 'fairness_rating' => 4, 'creative_respect_rating' => 5, 'body' => 'Great brand.',
    ]);
    expect($review->deal_id)->toBe($deal->id)->and((bool) $review->is_approved)->toBeFalse();
});
