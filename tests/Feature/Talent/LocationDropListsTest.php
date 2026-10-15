<?php

use App\Models\Talent;
use App\Support\Locations;

it('lists a country\'s cities, largest first', function () {
    $this->getJson('/locations/eg/cities')
        ->assertOk()
        ->assertJsonPath('data.0', 'Cairo')
        ->assertHeader('Cache-Control', 'max-age=86400, public');
});

it('404s an unknown country code', function () {
    $this->getJson('/locations/zz/cities')->assertNotFound();
});

it('ships a flag for every country', function () {
    foreach (Locations::countries() as $country) {
        expect(public_path('fama-ui/img/flags/'.strtolower($country['code']).'.svg'))->toBeFile();
    }
});

it('saves a country from the list and stores its canonical name', function () {
    $talent = Talent::factory()->create();

    $this->actingAs($talent, 'talent')
        ->patchJson('/talent/profile', ['base_country' => 'UAE', 'base_city' => 'Dubai'])
        ->assertOk();

    expect($talent->fresh()->base_country)->toBe('United Arab Emirates');
});

it('rejects a country that is not on the list', function () {
    $talent = Talent::factory()->create(['base_country' => 'Egypt']);

    $this->actingAs($talent, 'talent')
        ->patchJson('/talent/profile', ['base_country' => 'Atlantis'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('base_country');
});

it('lets an unknown legacy country ride along unchanged', function () {
    $talent = Talent::factory()->create(['base_country' => 'Middle Earth']);

    $this->actingAs($talent, 'talent')
        ->patchJson('/talent/profile', ['base_country' => 'Middle Earth', 'display_name' => 'New'])
        ->assertOk();
});

it('accepts a city typed in by hand (small towns are not on the list)', function () {
    $talent = Talent::factory()->create();

    $this->actingAs($talent, 'talent')
        ->patchJson('/talent/profile', ['base_country' => 'Egypt', 'base_city' => 'Siwa Oasis'])
        ->assertOk();

    expect($talent->fresh()->base_city)->toBe('Siwa Oasis');
});

it('canonicalises legacy country spellings in the migration', function () {
    $talent = Talent::factory()->create(['base_country' => 'UAE']);
    $odd = Talent::factory()->create(['base_country' => 'Middle Earth']);

    (require database_path('migrations/2026_10_15_000100_canonicalise_base_country_names.php'))->up();

    expect($talent->fresh()->base_country)->toBe('United Arab Emirates')
        ->and($odd->fresh()->base_country)->toBe('Middle Earth');
});

it('renders the editor with the country and city drop lists', function () {
    $talent = Talent::factory()->create(['base_country' => 'Egypt', 'base_city' => 'Cairo']);

    $this->actingAs($talent, 'talent')->get(route('talent.profile.edit'))
        ->assertOk()
        ->assertSee('id="fCountry"', false)
        ->assertSee('id="fCity"', false)
        ->assertSee('famaCombo', false);
});
