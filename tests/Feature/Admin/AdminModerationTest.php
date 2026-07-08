<?php

use App\Models\Brand;
use App\Models\BrandReview;
use App\Models\Campaign;
use App\Models\Review;
use App\Models\Talent;
use App\Models\TalentType;
use App\Models\User;
use App\Services\BrandModerationService;
use App\Services\CampaignOversightService;
use App\Services\ProfessionCatalogService;
use App\Services\ReviewModerationService;
use App\Services\TalentModerationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TalentTypeSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
    $this->powerless = User::factory()->create();
});

it('suspends, soft-deletes and restores a talent (audited)', function () {
    $talent = Talent::factory()->create(['status' => 'live', 'is_published' => true]);
    $svc = app(TalentModerationService::class);

    $svc->suspend($this->admin, $talent, 'spam');
    expect($talent->fresh()->status->getValue())->toBe('suspended');

    $svc->softDelete($this->admin, $talent);
    expect(Talent::withTrashed()->find($talent->id)->trashed())->toBeTrue();

    $svc->restore($this->admin, $talent);
    expect(Talent::find($talent->id))->not->toBeNull();

    $activity = Activity::inLog('moderation')->where('description', 'talent.suspended')->first();
    expect($activity)->not->toBeNull();
    expect((int) $activity->causer_id)->toBe($this->admin->id);
    expect(data_get($activity->properties, 'reason'))->toBe('spam');
});

it('verifies (one-way) and suspends a brand', function () {
    $brand = Brand::factory()->create();
    $svc = app(BrandModerationService::class);

    $svc->verify($this->admin, $brand);
    expect((bool) $brand->fresh()->is_verified)->toBeTrue();

    $svc->suspend($this->admin, $brand);
    expect($brand->fresh()->status->getValue())->toBe('suspended');
});

it('approves talent reviews in batch', function () {
    $reviews = Review::factory()->count(3)->pending()->create();

    $count = app(ReviewModerationService::class)->approveBatch($this->admin, $reviews->pluck('id')->all());

    expect($count)->toBe(3);
    expect(Review::where('is_approved', true)->count())->toBe(3);
    expect(Activity::inLog('moderation')->where('description', 'review.approved')->count())->toBe(3);
});

it('moderates a brand review', function () {
    $review = BrandReview::factory()->pending()->create();

    app(ReviewModerationService::class)->approveBrandReview($this->admin, $review);

    expect((bool) $review->fresh()->is_approved)->toBeTrue();
});

it('force-privates and cancels a campaign', function () {
    $campaign = Campaign::factory()->create(['status' => 'open', 'is_public' => true]);
    $svc = app(CampaignOversightService::class);

    $svc->forcePrivate($this->admin, $campaign);
    expect((bool) $campaign->fresh()->is_public)->toBeFalse();

    $svc->cancel($this->admin, $campaign, 'policy breach');
    expect($campaign->fresh()->status->getValue())->toBe('cancelled');
});

it('edits a talent-type default_blocks (future seeds only) and adds a profession', function () {
    $this->seed(TalentTypeSeeder::class);
    $type = TalentType::where('slug', 'model')->firstOrFail();
    $svc = app(ProfessionCatalogService::class);

    $svc->updateDefaultBlocks($this->admin, $type, ['hero', 'gallery']);
    expect($type->fresh()->default_blocks)->toBe(['hero', 'gallery']);

    $new = $svc->addProfession($this->admin, [
        'name' => ['en' => 'Voice Artist', 'ar' => 'فنان صوت'],
        'category' => 'creative',
        'default_blocks' => ['hero'],
    ]);
    expect($new->slug)->toBe('voice-artist');
    expect(TalentType::where('slug', 'voice-artist')->exists())->toBeTrue();

    $activity = Activity::inLog('catalog')->where('description', 'talent_type.default_blocks_updated')->first();
    expect($activity)->not->toBeNull();
    expect((int) $activity->causer_id)->toBe($this->admin->id);
});

it('denies moderation to an admin without moderate-content', function () {
    $talent = Talent::factory()->create(['status' => 'live']);

    expect(fn () => app(TalentModerationService::class)->suspend($this->powerless, $talent))
        ->toThrow(AuthorizationException::class);
});

it('denies catalog edits to an admin without manage-flows', function () {
    $this->seed(TalentTypeSeeder::class);
    $type = TalentType::where('slug', 'model')->firstOrFail();

    expect(fn () => app(ProfessionCatalogService::class)->updateDefaultBlocks($this->powerless, $type, ['hero']))
        ->toThrow(AuthorizationException::class);
});
