<?php

use App\Contracting\ContractDocument;
use App\Models\Brand;
use App\Models\ContractFlow;
use App\Models\ContractTemplate;
use App\Models\Talent;
use App\Models\User;
use App\Services\ContractService;
use App\Services\ContractTemplateService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
    $this->actingAs($this->admin, 'admin');
    $this->service = app(ContractTemplateService::class);
});

it('creates a template with the default clause set', function () {
    $template = $this->service->create($this->admin, ['name' => 'Fama Standard']);

    expect($template->slug)->not->toBeEmpty();
    expect($template->clauses)->toHaveCount(6);
    expect($template->deposit_pct)->toBe(50);
    expect((bool) $template->is_active)->toBeTrue();
});

it('updates terms and a custom clause list', function () {
    $template = ContractTemplate::factory()->create();

    $this->service->update($this->admin, $template, [
        'name' => 'Crew day-rate',
        'deposit_pct' => 30,
        'cancellation_forfeit_pct' => 25,
        'revision_rounds' => 3,
        'clauses' => [
            ['label' => 'Payment', 'body' => '{deposit_pct}% down · {rest_pct}% on delivery'],
            ['label' => 'Kit', 'body' => 'Talent supplies their own equipment.'],
        ],
    ]);

    $template->refresh();
    expect($template->name)->toBe('Crew day-rate');
    expect($template->deposit_pct)->toBe(30);
    expect($template->clauses)->toHaveCount(2);
    expect($template->clauses[1]['label'])->toBe('Kit');
});

it('enforces one default per applies_to scope', function () {
    $a = ContractTemplate::factory()->create(['applies_to' => null]);
    $b = ContractTemplate::factory()->create(['applies_to' => null]);

    $this->service->setDefault($this->admin, $a);
    $this->service->setDefault($this->admin, $b);

    expect($a->fresh()->is_default)->toBeFalse();
    expect($b->fresh()->is_default)->toBeTrue();
});

it('protects a default template from deactivation and deletion', function () {
    $template = ContractTemplate::factory()->default()->create();

    expect(fn () => $this->service->setActive($this->admin, $template, false))
        ->toThrow(Illuminate\Validation\ValidationException::class);
    expect(fn () => $this->service->delete($this->admin, $template))
        ->toThrow(Illuminate\Validation\ValidationException::class);
});

it('denies template authoring to an admin without manage-flows', function () {
    $powerless = User::factory()->create();

    expect(fn () => $this->service->create($powerless, ['name' => 'X']))
        ->toThrow(AuthorizationException::class);
});

it('renders a live preview from draft terms + sample inputs (resolved tokens)', function () {
    $response = $this->postJson('/admin/contract-templates/preview', [
        'deposit_pct' => 40,
        'deposit_holder' => 'Fama',
        'cancellation_forfeit_pct' => 60,
        'revision_rounds' => 2,
        'preview_currency' => 'AED',
        'intro' => 'Between {brand} and {talent}',
        'clauses' => [
            ['label' => 'Payment', 'body' => '{deposit_pct}% down, held by {deposit_holder} · {rest_pct}% on delivery.'],
            ['label' => 'Cancellation', 'body' => 'Forfeits {forfeit_pct}%.'],
        ],
        'sample' => ['talent' => 'Yasmine K.', 'brand' => 'Studio Nour', 'scope' => '3 images', 'total' => 5000],
    ])->assertOk();

    $html = $response->json('data.html');
    expect($html)->toContain('Between Studio Nour and Yasmine K.');
    expect($html)->toContain('40% down, held by Fama · 60% on delivery.');
    expect($html)->toContain('Forfeits 60%.');
    // 40% of 5000 = 2,000 AED down.
    expect($html)->toContain('2,000 AED');
});

it('attaches a template to a flow over HTTP and returns it in the picker list', function () {
    $flow = ContractFlow::factory()->create();
    $template = ContractTemplate::factory()->create(['name' => 'Attached']);

    $this->patchJson("/admin/flows/{$flow->id}", [
        'name' => $flow->name,
        'contract_template_id' => $template->id,
    ])->assertOk();

    expect($flow->fresh()->contract_template_id)->toBe($template->id);

    $this->getJson("/admin/flows/{$flow->id}/data")
        ->assertOk()
        ->assertJsonPath('data.flow.contract_template_id', $template->id)
        ->assertJsonPath('data.templates.0.id', $template->id);
});

it('freezes the template terms onto a contract at creation (ADR-V snapshot)', function () {
    $template = ContractTemplate::factory()->create(['deposit_pct' => 50]);
    $flow = ContractFlow::factory()->standard()->create(['contract_template_id' => $template->id]);

    $contract = app(ContractService::class)->initiate([
        'brand_id' => Brand::factory()->create(['name' => 'Studio Nour'])->id,
        'talent_id' => Talent::factory()->create(['display_name' => 'Yasmine K.'])->id,
        'title' => 'Lookbook',
        'initiated_by' => 'brand',
    ], $flow);

    expect($contract->terms_snapshot)->not->toBeNull();
    expect($contract->terms_snapshot['deposit_pct'])->toBe(50);

    $doc = ContractDocument::fromContract($contract->load(['brand', 'talent', 'flow.contractTemplate']));
    expect($doc['intro'])->toBe('Between Studio Nour and Yasmine K.');
    expect($doc['clauses'])->not->toBeEmpty();
});

it('builds the contract document from prior steps (brief, dates, priced quote)', function () {
    $template = ContractTemplate::factory()->create(['deposit_pct' => 50]);
    $flow = ContractFlow::factory()->standard()->create(['contract_template_id' => $template->id]);
    $svc = app(ContractService::class);

    $contract = $svc->initiate([
        'brand_id' => Brand::factory()->create(['name' => 'Studio Nour'])->id,
        'talent_id' => Talent::factory()->create(['display_name' => 'Yasmine K.'])->id,
        'title' => 'Lookbook', 'initiated_by' => 'brand',
    ], $flow);

    // brief (brand) writes scope + start/end dates onto the contract…
    $contract = $svc->advance($contract, ['fields' => [
        'scope' => '3 lookbook images', 'start_date' => '2026-09-01', 'end_date' => '2026-09-03', 'budget' => 'x',
    ]], 'brand', $contract->brand);
    // …quote (talent) writes the amount + pricing unit.
    $contract = $svc->advance($contract, ['fields' => ['amount' => 4500, 'pricing_unit' => 'day']], 'talent', $contract->talent);

    $contract->refresh();
    expect($contract->brief)->toBe('3 lookbook images');
    expect($contract->pricing_unit)->toBe('day');
    expect((float) $contract->agreed_amount)->toBe(4500.0);
    expect($contract->start_date->toDateString())->toBe('2026-09-01');

    $doc = ContractDocument::fromContract($contract->load(['brand', 'talent', 'flow.contractTemplate']));
    $fee = collect($doc['rows'])->firstWhere('label', 'Total fee')['value'];
    expect($fee)->toBe('4,500 EGP / day');
    expect(collect($doc['rows'])->firstWhere('label', 'Scope')['value'])->toBe('3 lookbook images');
});

it('does not rewrite a live contract when the template is later edited', function () {
    $template = ContractTemplate::factory()->create(['deposit_pct' => 50]);
    $flow = ContractFlow::factory()->standard()->create(['contract_template_id' => $template->id]);

    $contract = app(ContractService::class)->initiate([
        'brand_id' => Brand::factory()->create()->id,
        'talent_id' => Talent::factory()->create()->id,
        'title' => 'Lookbook',
        'initiated_by' => 'brand',
    ], $flow);

    // Editing the template afterwards must not touch the in-flight contract.
    $this->service->update($this->admin, $template, ['deposit_pct' => 10]);

    expect($contract->fresh()->terms_snapshot['deposit_pct'])->toBe(50);
});
