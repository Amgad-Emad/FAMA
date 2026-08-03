<?php

use App\Models\Brand;
use App\Models\ContractFlow;
use App\Models\Talent;
use App\Models\User;
use App\Services\ContractFlowBuilderService;
use App\Services\ContractService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
    $this->actingAs($this->admin, 'admin'); // so LogsActivity captures the causer
    $this->builder = app(ContractFlowBuilderService::class);
});

it('builds a draft flow, adds ordered steps, and activates it', function () {
    $flow = $this->builder->createFlow($this->admin, ['name' => 'Booking']);
    expect($flow->status->getValue())->toBe('draft');
    expect((bool) $flow->is_active)->toBeFalse();

    $this->builder->addStep($this->admin, $flow, ['key' => 'brief', 'name' => 'Brief', 'actor' => 'brand', 'step_type' => 'form']);
    $this->builder->addStep($this->admin, $flow, ['key' => 'quote', 'name' => 'Quote', 'actor' => 'talent', 'step_type' => 'form']);
    expect($flow->steps()->count())->toBe(2);
    expect($flow->steps()->pluck('position')->all())->toBe([0, 1]);

    $flow = $this->builder->activate($this->admin, $flow);
    expect($flow->status->getValue())->toBe('active');
    expect((bool) $flow->is_active)->toBeTrue(); // synced projection
});

it('keeps a single default per applies_to scope', function () {
    $a = $this->builder->createFlow($this->admin, ['name' => 'A']);
    $b = $this->builder->createFlow($this->admin, ['name' => 'B']);

    $this->builder->markDefault($this->admin, $a);
    $this->builder->markDefault($this->admin, $b);

    expect((bool) $a->fresh()->is_default)->toBeFalse();
    expect((bool) $b->fresh()->is_default)->toBeTrue();
});

it('archives a flow and clears its default', function () {
    $flow = $this->builder->createFlow($this->admin, ['name' => 'Old']);
    $this->builder->activate($this->admin, $flow);
    $this->builder->markDefault($this->admin, $flow);

    $flow = $this->builder->archive($this->admin, $flow);

    expect($flow->status->getValue())->toBe('archived');
    expect((bool) $flow->is_active)->toBeFalse();
    expect((bool) $flow->is_default)->toBeFalse();
});

it('editing a flow only affects future contracts (existing contract keeps its snapshot)', function () {
    $flow = $this->builder->createFlow($this->admin, ['name' => 'Live']);
    $this->builder->addStep($this->admin, $flow, ['key' => 'brief', 'name' => 'Original Brief', 'actor' => 'brand', 'step_type' => 'form', 'settings' => ['fields' => ['scope']]]);
    $this->builder->addStep($this->admin, $flow, ['key' => 'done', 'name' => 'Done', 'actor' => 'system', 'step_type' => 'info']);
    $this->builder->activate($this->admin, $flow);

    $contract = app(ContractService::class)->initiate([
        'brand_id' => Brand::factory()->create()->id,
        'talent_id' => Talent::factory()->create()->id,
        'title' => 'Shoot', 'initiated_by' => 'brand',
    ], $flow);

    $step = $flow->steps()->where('key', 'brief')->first();
    $this->builder->updateStep($this->admin, $step, ['name' => 'Changed Brief']);

    expect($flow->steps()->where('key', 'brief')->first()->name)->toBe('Changed Brief');
    expect($contract->steps()->where('key', 'brief')->first()->name)->toBe('Original Brief');
});

it('audits flow authoring with the admin as causer', function () {
    $this->builder->createFlow($this->admin, ['name' => 'Audited']);

    $activity = Activity::inLog('contract_flow')->latest('id')->first();
    expect($activity->subject_type)->toBe(ContractFlow::class);
    expect((int) $activity->causer_id)->toBe($this->admin->id);
});

it('denies flow authoring to an admin without manage-flows', function () {
    $powerless = User::factory()->create();

    expect(fn () => $this->builder->createFlow($powerless, ['name' => 'Nope']))
        ->toThrow(AuthorizationException::class);
});
