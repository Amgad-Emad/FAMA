<?php

use App\Models\Talent;
use App\Models\User;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\TalentTypeSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed([TalentTypeSeeder::class, BlockTypeSeeder::class]);
});

$pages = [
    'talent.dashboard',
    'talent.profile.edit',
    'talent.professions',
    'talent.services',
    'talent.availability',
    'talent.reviews',
    'talent.affiliations',
    'talent.account',
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
