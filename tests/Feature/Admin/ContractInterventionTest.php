<?php

use App\Models\Brand;
use App\Models\Contract;
use App\Models\ContractFlow;
use App\Models\Talent;
use App\Models\User;
use App\Services\ContractInterventionService;
use App\Services\ContractService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
    $this->powerless = User::factory()->create();
    $this->intervention = app(ContractInterventionService::class);
});

function interventionContract(): Contract
{
    $flow = ContractFlow::factory()->create();
    $flow->steps()->createMany([
        ['key' => 'brief', 'name' => 'Brief', 'actor' => 'brand', 'step_type' => 'form', 'position' => 0, 'is_required' => true, 'is_skippable' => false, 'settings' => ['fields' => ['scope']]],
        ['key' => 'shoot', 'name' => 'Shoot', 'actor' => 'talent', 'step_type' => 'upload', 'position' => 1, 'is_required' => true, 'is_skippable' => false, 'settings' => []],
        ['key' => 'done', 'name' => 'Done', 'actor' => 'system', 'step_type' => 'info', 'position' => 2, 'is_required' => true, 'is_skippable' => false, 'settings' => []],
    ]);

    return app(ContractService::class)->initiate([
        'brand_id' => Brand::factory()->create()->id,
        'talent_id' => Talent::factory()->create()->id,
        'title' => 'Shoot', 'initiated_by' => 'brand',
    ], $flow);
}

it('overrides a stuck step, advances the contract, and audits it', function () {
    $contract = interventionContract(); // current: brief (awaiting_brand)

    $this->intervention->overrideStep($this->admin, $contract, 'brand unresponsive');

    $contract->refresh();
    expect($contract->currentStep->key)->toBe('shoot'); // brief force-completed, next active
    $activity = Activity::inLog('contract_intervention')->where('description', 'contract.step_overridden')->first();
    expect($activity)->not->toBeNull();
    expect((int) $activity->causer_id)->toBe($this->admin->id);
    expect(data_get($activity->properties, 'step'))->toBe('brief');
});

it('acts as the admin actor on a step that requires admin', function () {
    $flow = ContractFlow::factory()->create();
    $flow->steps()->createMany([
        ['key' => 'review', 'name' => 'Admin review', 'actor' => 'admin', 'step_type' => 'info', 'position' => 0, 'is_required' => true, 'is_skippable' => false, 'settings' => []],
        ['key' => 'done', 'name' => 'Done', 'actor' => 'system', 'step_type' => 'info', 'position' => 1, 'is_required' => true, 'is_skippable' => false, 'settings' => []],
    ]);
    $contract = app(ContractService::class)->initiate([
        'brand_id' => Brand::factory()->create()->id,
        'talent_id' => Talent::factory()->create()->id,
        'title' => 'X', 'initiated_by' => 'brand',
    ], $flow);
    expect($contract->currentStep->actor)->toBe('admin'); // awaiting the admin

    $this->intervention->advanceAsAdmin($this->admin, $contract, []);

    expect($contract->fresh()->status->getValue())->toBe('completed'); // admin ack → system done → complete
    expect(Activity::inLog('contract_intervention')->where('description', 'contract.advanced_by_admin')->count())->toBe(1);
});

it('cancels a contract', function () {
    $contract = interventionContract();

    $this->intervention->cancel($this->admin, $contract, 'fraud');

    expect($contract->fresh()->status->getValue())->toBe('cancelled');
});

it('nudges and reassigns a contract', function () {
    $contract = interventionContract();

    $this->intervention->nudge($this->admin, $contract, 'please respond');
    expect($contract->messages()->where('body', 'like', '%please respond%')->exists())->toBeTrue();

    $newTalent = Talent::factory()->create();
    $this->intervention->reassign($this->admin, $contract, $newTalent);
    expect($contract->fresh()->talent_id)->toBe($newTalent->id);
});

it('denies contract intervention to an admin without intervene-contracts', function () {
    $contract = interventionContract();

    expect(fn () => $this->intervention->cancel($this->powerless, $contract))
        ->toThrow(AuthorizationException::class);
});
