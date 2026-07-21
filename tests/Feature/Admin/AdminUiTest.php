<?php

use App\Models\Brand;
use App\Models\BrandProject;
use App\Models\Contract;
use App\Models\ContractFlow;
use App\Models\Review;
use App\Models\Talent;
use App\Models\TalentType;
use App\Models\User;
use App\Services\ContractService;
use App\Services\SettingsService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TalentTypeSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
    $this->powerless = User::factory()->create(); // authenticated admin, no permissions
});

function adminConsoleContract(): Contract
{
    $flow = ContractFlow::factory()->create();
    $flow->steps()->createMany([
        ['key' => 'brief', 'name' => 'Brief', 'actor' => 'brand', 'step_type' => 'form', 'position' => 0, 'is_required' => true, 'is_skippable' => false, 'settings' => ['fields' => ['scope']]],
        ['key' => 'shoot', 'name' => 'Shoot', 'actor' => 'talent', 'step_type' => 'upload', 'position' => 1, 'is_required' => true, 'is_skippable' => false, 'settings' => []],
    ]);

    return app(ContractService::class)->initiate([
        'brand_id' => Brand::factory()->create()->id,
        'talent_id' => Talent::factory()->create()->id,
        'title' => 'X', 'initiated_by' => 'brand',
    ], $flow);
}

it('renders every admin page for a super-admin', function () {
    $this->seed(TalentTypeSeeder::class);

    foreach (['/admin/dashboard', '/admin/flows', '/admin/skills', '/admin/moderation', '/admin/contracts', '/admin/activity', '/admin/settings', '/admin/users'] as $url) {
        $this->actingAs($this->admin, 'admin')->get($url)->assertOk();
    }
});

it('serves every admin data endpoint (JSON)', function () {
    $this->seed(TalentTypeSeeder::class);
    $flow = ContractFlow::factory()->create();
    $flow->steps()->create(['key' => 'brief', 'name' => 'Brief', 'actor' => 'brand', 'step_type' => 'form', 'position' => 0, 'is_required' => true, 'is_skippable' => false, 'settings' => []]);
    Talent::factory()->create(['status' => 'live']);
    Review::factory()->pending()->create();

    foreach ([
        '/admin/flows/data', '/admin/skills/data', '/admin/moderation/talents',
        '/admin/moderation/reviews', '/admin/moderation/brands', '/admin/moderation/brand-reviews',
        '/admin/moderation/projects', '/admin/contracts/data', '/admin/activity/data', '/admin/users/data',
    ] as $url) {
        $this->actingAs($this->admin, 'admin')->getJson($url)->assertOk();
    }

    // The flow list surfaces its step + contract counts.
    $this->actingAs($this->admin, 'admin')->getJson('/admin/flows/data')
        ->assertJsonPath('data.0.steps_count', 1)
        ->assertJsonPath('data.0.contracts_count', 0);
});

it('gates admin pages by permission — a powerless admin is forbidden', function () {
    foreach (['/admin/flows', '/admin/moderation', '/admin/contracts', '/admin/settings', '/admin/users', '/admin/skills', '/admin/activity'] as $url) {
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

    expect(ContractFlow::find($id)->steps()->count())->toBe(1);
});

it('denies flow building to a non-authorized admin (403)', function () {
    $this->actingAs($this->powerless, 'admin')
        ->postJson('/admin/flows', ['name' => 'Nope'])
        ->assertForbidden();
});

it('moderates talents, reviews (batch), brands and projects', function () {
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

    $project = BrandProject::factory()->create(['status' => 'open']);
    $this->actingAs($this->admin, 'admin')->patchJson("/admin/moderation/projects/{$project->id}/cancel")->assertOk();
    expect($project->fresh()->status->getValue())->toBe('cancelled');
});

it('denies moderation to a non-authorized admin (403)', function () {
    $talent = Talent::factory()->create(['status' => 'live']);
    $this->actingAs($this->powerless, 'admin')
        ->patchJson("/admin/moderation/talents/{$talent->id}/suspend")
        ->assertForbidden();
});

it('edits talent-type default_blocks and adds a skill', function () {
    $this->seed(TalentTypeSeeder::class);
    $type = TalentType::where('slug', 'modeling')->firstOrFail();

    $this->actingAs($this->admin, 'admin')
        ->patchJson("/admin/skills/{$type->id}/blocks", ['default_blocks' => ['hero', 'gallery']])
        ->assertOk();
    expect($type->fresh()->default_blocks)->toBe(['hero', 'gallery']);

    $this->actingAs($this->admin, 'admin')
        ->postJson('/admin/skills', ['name' => ['en' => 'DJ'], 'category' => 'creative', 'default_blocks' => ['hero']])
        ->assertCreated();
    expect(TalentType::where('slug', 'dj')->exists())->toBeTrue();
});

it('overrides a stuck contract step and cancels a contract', function () {
    $contract = adminConsoleContract();
    $this->actingAs($this->admin, 'admin')
        ->postJson("/admin/contracts/{$contract->id}/override", ['note' => 'stuck'])
        ->assertOk();
    expect($contract->fresh()->currentStep->key)->toBe('shoot');

    $other = adminConsoleContract();
    $this->actingAs($this->admin, 'admin')
        ->postJson("/admin/contracts/{$other->id}/cancel", ['reason' => 'fraud'])
        ->assertOk();
    expect($other->fresh()->status->getValue())->toBe('cancelled');
});

it('denies contract intervention to a non-authorized admin (403)', function () {
    $contract = adminConsoleContract();
    $this->actingAs($this->powerless, 'admin')
        ->postJson("/admin/contracts/{$contract->id}/cancel")
        ->assertForbidden();
});

it('saves settings and creates an admin with a role', function () {
    $this->actingAs($this->admin, 'admin')->patchJson('/admin/settings', ['default_currency' => 'USD'])->assertOk();
    expect(app(SettingsService::class)->defaultCurrency())->toBe('USD');

    $this->actingAs($this->admin, 'admin')
        ->postJson('/admin/users', ['account_type' => 'admin', 'name' => 'Mod', 'email' => 'mod@fama.test', 'password' => 'password123', 'roles' => ['moderator']])
        ->assertCreated();
    expect(User::where('email', 'mod@fama.test')->first()->hasRole('moderator'))->toBeTrue();
});
