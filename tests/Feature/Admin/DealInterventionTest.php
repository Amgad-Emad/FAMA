<?php

use App\Models\Brand;
use App\Models\Deal;
use App\Models\DealFlow;
use App\Models\Talent;
use App\Models\User;
use App\Services\DealInterventionService;
use App\Services\DealService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
    $this->powerless = User::factory()->create();
    $this->intervention = app(DealInterventionService::class);
});

function interventionDeal(): Deal
{
    $flow = DealFlow::factory()->create();
    $flow->steps()->createMany([
        ['key' => 'brief', 'name' => 'Brief', 'actor' => 'brand', 'step_type' => 'form', 'position' => 0, 'is_required' => true, 'is_skippable' => false, 'settings' => ['fields' => ['scope']]],
        ['key' => 'shoot', 'name' => 'Shoot', 'actor' => 'talent', 'step_type' => 'upload', 'position' => 1, 'is_required' => true, 'is_skippable' => false, 'settings' => []],
        ['key' => 'done', 'name' => 'Done', 'actor' => 'system', 'step_type' => 'info', 'position' => 2, 'is_required' => true, 'is_skippable' => false, 'settings' => []],
    ]);

    return app(DealService::class)->initiate([
        'brand_id' => Brand::factory()->create()->id,
        'talent_id' => Talent::factory()->create()->id,
        'title' => 'Shoot', 'initiated_by' => 'brand',
    ], $flow);
}

it('overrides a stuck step, advances the deal, and audits it', function () {
    $deal = interventionDeal(); // current: brief (awaiting_brand)

    $this->intervention->overrideStep($this->admin, $deal, 'brand unresponsive');

    $deal->refresh();
    expect($deal->currentStep->key)->toBe('shoot'); // brief force-completed, next active
    $activity = Activity::inLog('deal_intervention')->where('description', 'deal.step_overridden')->first();
    expect($activity)->not->toBeNull();
    expect((int) $activity->causer_id)->toBe($this->admin->id);
    expect(data_get($activity->properties, 'step'))->toBe('brief');
});

it('acts as the admin actor on a step that requires admin', function () {
    $flow = DealFlow::factory()->create();
    $flow->steps()->createMany([
        ['key' => 'review', 'name' => 'Admin review', 'actor' => 'admin', 'step_type' => 'info', 'position' => 0, 'is_required' => true, 'is_skippable' => false, 'settings' => []],
        ['key' => 'done', 'name' => 'Done', 'actor' => 'system', 'step_type' => 'info', 'position' => 1, 'is_required' => true, 'is_skippable' => false, 'settings' => []],
    ]);
    $deal = app(DealService::class)->initiate([
        'brand_id' => Brand::factory()->create()->id,
        'talent_id' => Talent::factory()->create()->id,
        'title' => 'X', 'initiated_by' => 'brand',
    ], $flow);
    expect($deal->currentStep->actor)->toBe('admin'); // awaiting the admin

    $this->intervention->advanceAsAdmin($this->admin, $deal, []);

    expect($deal->fresh()->status->getValue())->toBe('completed'); // admin ack → system done → complete
    expect(Activity::inLog('deal_intervention')->where('description', 'deal.advanced_by_admin')->count())->toBe(1);
});

it('cancels a deal', function () {
    $deal = interventionDeal();

    $this->intervention->cancel($this->admin, $deal, 'fraud');

    expect($deal->fresh()->status->getValue())->toBe('cancelled');
});

it('nudges and reassigns a deal', function () {
    $deal = interventionDeal();

    $this->intervention->nudge($this->admin, $deal, 'please respond');
    expect($deal->messages()->where('body', 'like', '%please respond%')->exists())->toBeTrue();

    $newTalent = Talent::factory()->create();
    $this->intervention->reassign($this->admin, $deal, $newTalent);
    expect($deal->fresh()->talent_id)->toBe($newTalent->id);
});

it('denies deal intervention to an admin without intervene-deals', function () {
    $deal = interventionDeal();

    expect(fn () => $this->intervention->cancel($this->powerless, $deal))
        ->toThrow(AuthorizationException::class);
});
