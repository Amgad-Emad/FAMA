<?php

use App\Models\Equipment;
use App\Models\SoftwareStack;
use App\Models\Talent;
use App\Models\TalentType;
use Database\Seeders\TalentTypeSeeder;

beforeEach(fn () => $this->withoutVite());

it('renders the discovery page', function () {
    $this->get(route('discover'))->assertOk();
});

it('lists only published talents, paginated', function () {
    Talent::factory()->count(3)->create();
    Talent::factory()->draft()->create();

    $this->getJson(route('discover.search'))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('meta.pagination.total', 3);
});

it('filters by profession type through the pivot', function () {
    $this->seed(TalentTypeSeeder::class);
    $model = TalentType::where('slug', 'model')->firstOrFail();
    $photographer = TalentType::where('slug', 'photographer')->firstOrFail();

    $a = Talent::factory()->create();
    $a->talentTypes()->attach($model->id, ['is_primary' => true, 'position' => 0]);
    $b = Talent::factory()->create();
    $b->talentTypes()->attach($photographer->id, ['is_primary' => true, 'position' => 0]);

    $response = $this->getJson(route('discover.search', ['filter' => ['type' => 'model']]))->assertOk();

    expect($response->json('meta.pagination.total'))->toBe(1);
    expect($response->json('data.0.slug'))->toBe($a->slug);
});

it('filters by availability, location, equipment and software', function () {
    $available = Talent::factory()->create(['availability_status' => 'available', 'base_city' => 'Cairo']);
    $booked = Talent::factory()->create(['availability_status' => 'booked', 'base_city' => 'Dubai']);
    Equipment::factory()->for($available)->create(['category' => 'camera']);
    SoftwareStack::factory()->for($booked)->create(['software_name' => 'Figma']);

    expect($this->getJson(route('discover.search', ['filter' => ['availability' => 'available']]))->json('meta.pagination.total'))->toBe(1);
    expect($this->getJson(route('discover.search', ['filter' => ['city' => 'Cairo']]))->json('meta.pagination.total'))->toBe(1);
    expect($this->getJson(route('discover.search', ['filter' => ['equipment' => 'camera']]))->json('data.0.slug'))->toBe($available->slug);
    expect($this->getJson(route('discover.search', ['filter' => ['software' => 'Figma']]))->json('data.0.slug'))->toBe($booked->slug);
});

it('paginates results (12 per page)', function () {
    Talent::factory()->count(15)->create();

    $response = $this->getJson(route('discover.search'))->assertOk();

    expect($response->json('data'))->toHaveCount(12);
    expect($response->json('meta.pagination.last_page'))->toBe(2);
    expect($this->getJson(route('discover.search', ['page' => 2]))->json('data'))->toHaveCount(3);
});
