<?php

use App\Models\Brand;
use App\Models\Deal;
use App\Models\DealFlow;
use App\Models\Talent;
use App\Models\TalentType;
use App\Models\User;
use App\Services\DealFlowBuilderService;
use App\Services\ProfessionCatalogService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TalentTypeSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
    $this->actingAs($this->admin, 'admin');
});

// ---------------------------------------------------------------------------
// Every admin mutation is activity-logged (with the admin as causer).
// ---------------------------------------------------------------------------

it('audits flow lifecycle transitions with the admin as causer', function () {
    $builder = app(DealFlowBuilderService::class);
    $flow = DealFlow::factory()->draft()->create();
    Activity::query()->delete(); // drop factory noise

    $builder->activate($this->admin, $flow);
    $builder->markDefault($this->admin, $flow);
    $builder->archive($this->admin, $flow);

    $entries = Activity::inLog('deal_flow')->get();
    expect($entries)->not->toBeEmpty();
    expect($entries->every(fn ($a) => (int) $a->causer_id === $this->admin->id))->toBeTrue();
});

it('audits settings updates', function () {
    $this->patchJson('/admin/settings', ['default_currency' => 'USD'])->assertOk();

    expect(Activity::inLog('settings')->where('description', 'settings.updated')->count())->toBe(1);
});

it('audits admin-user create, role-sync and delete', function () {
    $id = $this->postJson('/admin/users', ['name' => 'Mod', 'email' => 'm@fama.test', 'password' => 'password123', 'roles' => ['moderator']])
        ->assertCreated()->json('data.id');
    $this->patchJson("/admin/users/{$id}/roles", ['roles' => ['support']])->assertOk();
    $this->deleteJson("/admin/users/{$id}")->assertOk();

    $descriptions = Activity::inLog('admin_users')->pluck('description')->all();
    expect($descriptions)->toContain('admin_user.created', 'admin_user.roles_synced', 'admin_user.deleted');
});

// ---------------------------------------------------------------------------
// Transactions + fail-logging.
// ---------------------------------------------------------------------------

it('rolls back and fail-logs to the admin channel on a service failure', function () {
    $this->seed(TalentTypeSeeder::class);
    Log::shouldReceive('channel')->with('admin')->andReturnSelf();
    Log::shouldReceive('error')->atLeast()->once();

    $before = Talent::count();

    // 'model' slug already exists → unique violation inside the transaction.
    expect(fn () => app(ProfessionCatalogService::class)->addProfession($this->admin, [
        'name' => ['en' => 'Dupe'], 'slug' => 'model', 'category' => 'model', 'default_blocks' => [],
    ]))->toThrow(QueryException::class);

    expect(TalentType::where('slug', 'model')->count())->toBe(1); // no partial write
});

// ---------------------------------------------------------------------------
// N+1 audits on paginated admin lists.
// ---------------------------------------------------------------------------

it('paginates the moderation talent queue without N+1', function () {
    Talent::factory()->count(3)->create(['status' => 'live']);
    $this->getJson('/admin/moderation/talents'); // warm

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->getJson('/admin/moderation/talents')->assertOk();
    $small = count(DB::getQueryLog());

    Talent::factory()->count(6)->create(['status' => 'live']);
    DB::flushQueryLog();
    $this->getJson('/admin/moderation/talents')->assertOk();
    $big = count(DB::getQueryLog());

    expect($big)->toBe($small);
});

it('lists the deal console without N+1 (brand/talent/step eager-loaded)', function () {
    $make = fn () => Deal::factory()->create();
    collect(range(1, 2))->each($make);
    $this->getJson('/admin/deals/data'); // warm

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->getJson('/admin/deals/data')->assertOk();
    $small = count(DB::getQueryLog());

    collect(range(1, 4))->each($make);
    DB::flushQueryLog();
    $this->getJson('/admin/deals/data')->assertOk();
    $big = count(DB::getQueryLog());

    expect($big)->toBe($small);
});

it('lists the activity log without N+1 (causer eager-loaded)', function () {
    $seed = function (int $n) {
        for ($i = 0; $i < $n; $i++) {
            activity('moderation')->causedBy($this->admin)->performedOn(Brand::factory()->create())->log('brand.verified');
        }
    };
    $seed(2);
    $this->getJson('/admin/activity/data'); // warm

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->getJson('/admin/activity/data')->assertOk();
    $small = count(DB::getQueryLog());

    $seed(4);
    DB::flushQueryLog();
    $this->getJson('/admin/activity/data')->assertOk();
    $big = count(DB::getQueryLog());

    expect($big)->toBe($small);
});
