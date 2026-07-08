<?php

use App\Models\Brand;
use App\Models\Campaign;
use App\Models\Deal;
use App\Models\DealFlow;
use App\Models\Review;
use App\Models\Talent;
use App\Models\TalentType;
use App\Models\User;
use App\Services\DealService;
use App\Services\SettingsService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TalentTypeSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
    $this->powerless = User::factory()->create(); // authenticated admin, no permissions
});

function adminConsoleDeal(): Deal
{
    $flow = DealFlow::factory()->create();
    $flow->steps()->createMany([
        ['key' => 'brief', 'name' => 'Brief', 'actor' => 'brand', 'step_type' => 'form', 'position' => 0, 'is_required' => true, 'is_skippable' => false, 'settings' => ['fields' => ['scope']]],
        ['key' => 'shoot', 'name' => 'Shoot', 'actor' => 'talent', 'step_type' => 'upload', 'position' => 1, 'is_required' => true, 'is_skippable' => false, 'settings' => []],
    ]);

    return app(DealService::class)->initiate([
        'brand_id' => Brand::factory()->create()->id,
        'talent_id' => Talent::factory()->create()->id,
        'title' => 'X', 'initiated_by' => 'brand',
    ], $flow);
}

it('renders every admin page for a super-admin', function () {
    $this->seed(TalentTypeSeeder::class);

    foreach (['/admin/dashboard', '/admin/flows', '/admin/professions', '/admin/moderation', '/admin/deals', '/admin/activity', '/admin/settings', '/admin/users'] as $url) {
        $this->actingAs($this->admin, 'admin')->get($url)->assertOk();
    }
});

it('gates admin pages by permission — a powerless admin is forbidden', function () {
    foreach (['/admin/flows', '/admin/moderation', '/admin/deals', '/admin/settings', '/admin/users', '/admin/professions', '/admin/activity'] as $url) {
        $this->actingAs($this->powerless, 'admin')->get($url)->assertForbidden();
    }
    // The dashboard is open to any authenticated admin.
    $this->actingAs($this->powerless, 'admin')->get('/admin/dashboard')->assertOk();
});

it('builds a flow, adds a step and activates it', function () {
    $response = $this->actingAs($this->admin, 'admin')
        ->postJson('/admin/flows', ['name' => 'Booking', 'applies_to' => 'model'])
        ->assertCreated();
    $id = $response->json('data.id');

    $this->actingAs($this->admin, 'admin')
        ->postJson("/admin/flows/{$id}/steps", ['key' => 'brief', 'name' => 'Brief', 'actor' => 'brand', 'step_type' => 'form'])
        ->assertCreated();

    $this->actingAs($this->admin, 'admin')
        ->patchJson("/admin/flows/{$id}/activate")
        ->assertOk()->assertJsonPath('data.status', 'active');

    expect(DealFlow::find($id)->steps()->count())->toBe(1);
});

it('denies flow building to a non-authorized admin (403)', function () {
    $this->actingAs($this->powerless, 'admin')
        ->postJson('/admin/flows', ['name' => 'Nope'])
        ->assertForbidden();
});

it('moderates talents, reviews (batch), brands and campaigns', function () {
    $talent = Talent::factory()->create(['status' => 'live']);
    $this->actingAs($this->admin, 'admin')->patchJson("/admin/moderation/talents/{$talent->id}/suspend")->assertOk();
    expect($talent->fresh()->status->getValue())->toBe('suspended');

    $reviews = Review::factory()->count(2)->pending()->create();
    $this->actingAs($this->admin, 'admin')
        ->postJson('/admin/moderation/reviews/batch', ['action' => 'approve', 'ids' => $reviews->pluck('id')->all()])
        ->assertOk()->assertJsonPath('data.count', 2);

    $brand = Brand::factory()->create();
    $this->actingAs($this->admin, 'admin')->patchJson("/admin/moderation/brands/{$brand->id}/verify")->assertOk();
    expect((bool) $brand->fresh()->is_verified)->toBeTrue();

    $campaign = Campaign::factory()->create(['status' => 'open']);
    $this->actingAs($this->admin, 'admin')->patchJson("/admin/moderation/campaigns/{$campaign->id}/cancel")->assertOk();
    expect($campaign->fresh()->status->getValue())->toBe('cancelled');
});

it('denies moderation to a non-authorized admin (403)', function () {
    $talent = Talent::factory()->create(['status' => 'live']);
    $this->actingAs($this->powerless, 'admin')
        ->patchJson("/admin/moderation/talents/{$talent->id}/suspend")
        ->assertForbidden();
});

it('edits talent-type default_blocks and adds a profession', function () {
    $this->seed(TalentTypeSeeder::class);
    $type = TalentType::where('slug', 'model')->firstOrFail();

    $this->actingAs($this->admin, 'admin')
        ->patchJson("/admin/professions/{$type->id}/blocks", ['default_blocks' => ['hero', 'gallery']])
        ->assertOk();
    expect($type->fresh()->default_blocks)->toBe(['hero', 'gallery']);

    $this->actingAs($this->admin, 'admin')
        ->postJson('/admin/professions', ['name' => ['en' => 'DJ'], 'category' => 'creative', 'default_blocks' => ['hero']])
        ->assertCreated();
    expect(TalentType::where('slug', 'dj')->exists())->toBeTrue();
});

it('overrides a stuck deal step and cancels a deal', function () {
    $deal = adminConsoleDeal();
    $this->actingAs($this->admin, 'admin')
        ->postJson("/admin/deals/{$deal->id}/override", ['note' => 'stuck'])
        ->assertOk();
    expect($deal->fresh()->currentStep->key)->toBe('shoot');

    $other = adminConsoleDeal();
    $this->actingAs($this->admin, 'admin')
        ->postJson("/admin/deals/{$other->id}/cancel", ['reason' => 'fraud'])
        ->assertOk();
    expect($other->fresh()->status->getValue())->toBe('cancelled');
});

it('denies deal intervention to a non-authorized admin (403)', function () {
    $deal = adminConsoleDeal();
    $this->actingAs($this->powerless, 'admin')
        ->postJson("/admin/deals/{$deal->id}/cancel")
        ->assertForbidden();
});

it('saves settings and creates an admin with a role', function () {
    $this->actingAs($this->admin, 'admin')->patchJson('/admin/settings', ['default_currency' => 'USD'])->assertOk();
    expect(app(SettingsService::class)->defaultCurrency())->toBe('USD');

    $this->actingAs($this->admin, 'admin')
        ->postJson('/admin/users', ['name' => 'Mod', 'email' => 'mod@fama.test', 'password' => 'password123', 'roles' => ['moderator']])
        ->assertCreated();
    expect(User::where('email', 'mod@fama.test')->first()->hasRole('moderator'))->toBeTrue();
});
