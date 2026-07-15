<?php

use App\Models\Brand;
use App\Models\ContractFlow;
use App\Models\Talent;
use App\Models\User;
use App\Services\ContractService;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\TalentTypeSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed([TalentTypeSeeder::class, BlockTypeSeeder::class]);
});

it('shows active contracts with whose-turn on the dashboard home', function () {
    $talent = Talent::factory()->create();
    $contracts = app(ContractService::class);
    $contract = $contracts->initiate([
        'brand_id' => Brand::factory()->create()->id, 'talent_id' => $talent->id,
        'title' => 'Autumn campaign shoot', 'initiated_by' => 'brand',
    ], ContractFlow::factory()->standard()->create());
    // brand submits the brief → now it's the talent's turn (awaiting_talent).
    $contracts->advance($contract, ['fields' => ['scope' => 'x', 'dates' => 'y', 'budget' => 'z']], 'brand', $contract->brand);

    $this->actingAs($talent, 'talent')->get(route('talent.dashboard'))
        ->assertOk()
        ->assertSee('Autumn campaign shoot')
        ->assertSee('Your turn');
});

$pages = [
    'talent.dashboard',
    'talent.profile.edit',
    'talent.reviews',
];

it('renders every dashboard page for the authenticated talent', function () use ($pages) {
    $talent = Talent::factory()->create();

    foreach ($pages as $page) {
        $this->actingAs($talent, 'talent')->get(route($page))->assertOk();
    }

    $this->actingAs($talent, 'talent')->get(route('talent.content', ['type' => 'gallery']))->assertOk();
});

it('redirects guests to login for every dashboard page', function () use ($pages) {
    foreach ($pages as $page) {
        $this->get(route($page))->assertRedirect(route('login'));
    }

    $this->get(route('talent.content', ['type' => 'gallery']))->assertRedirect(route('login'));
});

it('does not let an admin user reach the talent dashboard', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin, 'admin')->get(route('talent.dashboard'))->assertRedirect(route('login'));
});
