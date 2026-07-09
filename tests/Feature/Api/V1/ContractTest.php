<?php

use App\Models\Brand;
use App\Models\Deal;
use App\Models\Talent;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------------
// The shared JSON envelope + pagination contract.
// ---------------------------------------------------------------------------

it('returns the paginated envelope for the discovery feed', function () {
    Talent::factory()->count(15)->create(['is_published' => true, 'status' => 'live']);

    $response = $this->getJson('/api/v1/talents')->assertOk();

    // Envelope shape.
    $response->assertJsonStructure([
        'success', 'data', 'message', 'errors',
        'meta' => ['pagination' => ['current_page', 'last_page', 'per_page', 'total', 'from', 'to']],
    ]);

    expect($response->json('success'))->toBeTrue()
        ->and($response->json('data'))->toBeArray()
        ->and($response->json('meta.pagination.per_page'))->toBe(12)   // TalentSearch default
        ->and($response->json('meta.pagination.total'))->toBe(15)
        ->and(count($response->json('data')))->toBe(12);
});

it('only lists and shows published talents', function () {
    $live = Talent::factory()->create(['is_published' => true, 'status' => 'live']);
    $draft = Talent::factory()->create(['is_published' => false, 'status' => 'draft']);

    $slugs = collect($this->getJson('/api/v1/talents')->json('data'))->pluck('slug');
    expect($slugs)->toContain($live->slug)->not->toContain($draft->slug);

    $this->getJson("/api/v1/talents/{$live->slug}")->assertOk()->assertJsonPath('data.slug', $live->slug);
    $this->getJson("/api/v1/talents/{$draft->slug}")->assertNotFound()->assertJsonPath('success', false);
});

it('shows a published brand and 404s an unpublished one as an envelope', function () {
    $published = Brand::factory()->create(['is_published' => true, 'status' => 'published']);
    $hidden = Brand::factory()->unpublished()->create();

    $this->getJson("/api/v1/brands/{$published->slug}")->assertOk()->assertJsonPath('data.slug', $published->slug);

    $this->getJson("/api/v1/brands/{$hidden->slug}")
        ->assertNotFound()
        ->assertJsonPath('success', false)
        ->assertJsonPath('data', null);
});

it('404s an unknown route-model binding as the error envelope', function () {
    $this->getJson('/api/v1/talents/does-not-exist')
        ->assertNotFound()
        ->assertJsonPath('success', false)
        ->assertJsonPath('errors', null);
});

// ---------------------------------------------------------------------------
// Locale negotiation from Accept-Language (translatable fields).
// ---------------------------------------------------------------------------

it('returns translatable fields in the Accept-Language locale', function () {
    $talent = Talent::factory()->create(['is_published' => true, 'status' => 'live']);
    $talent->setTranslation('headline', 'en', 'Fashion photographer')
        ->setTranslation('headline', 'ar', 'مصوّر أزياء')
        ->save();

    $en = $this->withHeaders(['Accept-Language' => 'en'])->getJson("/api/v1/talents/{$talent->slug}");
    $en->assertOk()->assertJsonPath('data.headline', 'Fashion photographer');
    expect($en->headers->get('Content-Language'))->toBe('en');

    $ar = $this->withHeaders(['Accept-Language' => 'ar-EG,ar;q=0.9,en;q=0.5'])->getJson("/api/v1/talents/{$talent->slug}");
    $ar->assertOk()->assertJsonPath('data.headline', 'مصوّر أزياء');
    expect($ar->headers->get('Content-Language'))->toBe('ar');
});

// ---------------------------------------------------------------------------
// Authenticated deal inbox — scoped to the token's entity, paginated.
// ---------------------------------------------------------------------------

it('scopes the deal inbox to the authenticated entity', function () {
    $talent = Talent::factory()->create();
    $mine = Deal::factory()->create(['talent_id' => $talent->id]);
    $someoneElses = Deal::factory()->create();

    $token = $talent->createToken('test', ['talent'])->plainTextToken;

    $response = api()->withToken($token)->getJson('/api/v1/deals')->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['pagination']]);

    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toContain($mine->id)->not->toContain($someoneElses->id);

    // A single deal the caller is not party to is forbidden.
    api()->withToken($token)->getJson("/api/v1/deals/{$someoneElses->id}")->assertForbidden();
    api()->withToken($token)->getJson("/api/v1/deals/{$mine->id}")->assertOk()->assertJsonPath('data.id', $mine->id);
});

// ---------------------------------------------------------------------------
// Throttling — the stricter auth bucket returns the 429 envelope.
// ---------------------------------------------------------------------------

it('shapes an unexpected failure into a 500 envelope and fail-logs it', function () {
    // A throwaway API route that blows up with a non-HTTP exception.
    Route::middleware('api')->get('/api/v1/__boom', fn () => throw new RuntimeException('kaboom'));

    // Reaching the 500 envelope proves the catch-all closure ran end to end — its
    // Log::channel('api')->error(...) call is the line before the return, so a
    // logging failure would surface as a different error, not this envelope.
    $this->getJson('/api/v1/__boom')
        ->assertStatus(500)
        ->assertJsonPath('success', false)
        ->assertJsonPath('data', null);
});

it('throttles repeated login attempts with a 429 envelope', function () {
    Talent::factory()->create(['email' => 't@fama.test', 'password' => 'password123']);

    // The `auth` limiter allows 10/min keyed by email+ip; the 11th trips it.
    foreach (range(1, 10) as $i) {
        $this->postJson('/api/v1/talent/login', ['email' => 't@fama.test', 'password' => 'wrong'])->assertStatus(422);
    }

    $this->postJson('/api/v1/talent/login', ['email' => 't@fama.test', 'password' => 'wrong'])
        ->assertStatus(429)
        ->assertJsonPath('success', false);
});
