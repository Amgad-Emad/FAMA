<?php

use App\Models\AgencyAffiliation;
use App\Models\PortfolioItem;
use App\Models\Talent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('updates availability and travel settings', function () {
    $talent = Talent::factory()->create(['availability_status' => 'available']);

    $this->actingAs($talent, 'talent')
        ->patchJson(route('talent.availability.update'), [
            'availability_status' => 'booked',
            'willing_to_travel' => true,
            'travel_regions' => ['MENA', 'GCC'],
            'rate_tier' => 'premium',
        ])
        ->assertOk()
        ->assertJsonPath('data.availability_status', 'booked');

    $talent->refresh();
    expect($talent->availability_status->getValue())->toBe('booked');
    expect($talent->willing_to_travel)->toBeTrue();
    expect($talent->rate_tier)->toBe('premium');
});

it('publishes and unpublishes from the account page', function () {
    $talent = Talent::factory()->draft()->create(['display_name' => 'Pub']);

    $this->actingAs($talent, 'talent')->patchJson(route('talent.account.publish'), ['publish' => true])
        ->assertOk()->assertJsonPath('data.is_published', true);
    expect($talent->fresh()->is_published)->toBeTrue();

    $this->actingAs($talent, 'talent')->patchJson(route('talent.account.publish'), ['publish' => false])->assertOk();
    expect($talent->fresh()->is_published)->toBeFalse();
});

it('refuses to publish a profile with no display name (422)', function () {
    $talent = Talent::factory()->draft()->create(['display_name' => null]);

    $this->actingAs($talent, 'talent')->patchJson(route('talent.account.publish'), ['publish' => true])
        ->assertStatus(422)->assertJsonPath('success', false);
});

it('updates the account slug', function () {
    $talent = Talent::factory()->create();

    $this->actingAs($talent, 'talent')->patchJson(route('talent.account.update'), ['slug' => 'my-new-slug'])
        ->assertOk()->assertJsonPath('data.slug', 'my-new-slug');
    expect($talent->fresh()->slug)->toBe('my-new-slug');
});

it('adds an affiliation, ends it, and adds press', function () {
    $talent = Talent::factory()->create();

    $affiliationId = $this->actingAs($talent, 'talent')
        ->postJson(route('talent.affiliations.store'), ['agency_name' => 'Elite', 'representation_type' => 'exclusive'])
        ->assertCreated()->json('data.id');

    $this->actingAs($talent, 'talent')->patchJson(route('talent.affiliations.end', $affiliationId))->assertOk();
    expect(AgencyAffiliation::find($affiliationId)->is_current)->toBeFalse();

    $this->actingAs($talent, 'talent')
        ->postJson(route('talent.press.store'), ['publication' => 'Vogue', 'title' => 'Feature'])
        ->assertCreated();
    expect($talent->pressFeatures()->count())->toBe(1);
});

it('manages a gallery content editor: create, list, reorder, remove', function () {
    $talent = Talent::factory()->create();

    $id = $this->actingAs($talent, 'talent')
        ->postJson(route('talent.content.store', ['type' => 'gallery']), ['media_type' => 'image', 'caption' => ['en' => 'Shot 1']])
        ->assertCreated()->json('data.id');

    $this->actingAs($talent, 'talent')->getJson(route('talent.content.data', ['type' => 'gallery']))
        ->assertOk()->assertJsonPath('meta.pagination.total', 1);

    $this->actingAs($talent, 'talent')->patchJson(route('talent.content.reorder', ['type' => 'gallery']), ['order' => [$id]])->assertOk();

    $this->actingAs($talent, 'talent')->deleteJson(route('talent.content.destroy', ['type' => 'gallery', 'id' => $id]))->assertOk();
    expect(PortfolioItem::find($id))->toBeNull();
});

it('uploads media to a content item through the media library', function () {
    Storage::fake('public');
    $talent = Talent::factory()->create();

    $id = $this->actingAs($talent, 'talent')
        ->postJson(route('talent.content.store', ['type' => 'gallery']), ['media_type' => 'image'])
        ->assertCreated()->json('data.id');

    $this->actingAs($talent, 'talent')
        ->post(route('talent.content.media', ['type' => 'gallery', 'id' => $id]), ['file' => UploadedFile::fake()->image('shot.jpg', 400, 500)])
        ->assertOk();

    expect(PortfolioItem::find($id)->getMedia('gallery'))->toHaveCount(1);
});

it('404s for an unknown content type', function () {
    $talent = Talent::factory()->create();

    $this->actingAs($talent, 'talent')->getJson(route('talent.content.data', ['type' => 'nope']))->assertNotFound();
});

it('forbids managing another talent’s content item', function () {
    $owner = Talent::factory()->create();
    $item = PortfolioItem::factory()->for($owner)->create();
    $intruder = Talent::factory()->create();

    $this->actingAs($intruder, 'talent')
        ->deleteJson(route('talent.content.destroy', ['type' => 'gallery', 'id' => $item->id]))
        ->assertForbidden();
});
