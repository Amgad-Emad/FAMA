<?php

use App\Models\Talent;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\TalentTypeSeeder;

/*
 * The fama-ui shell after the co-founder's 30 Oct–1 Nov 2026 prototype pass:
 * a signed-in talent gets the wide-screen SIDE BAR (account, Create menu,
 * three link groups) alongside the ≤1100px top bar; visitors keep the top
 * bar only, now with a Join button.
 */

beforeEach(function () {
    $this->withoutVite();
    $this->seed([TalentTypeSeeder::class, BlockTypeSeeder::class]);
});

it('renders the talent side bar with its groups, Create menu and account menu', function () {
    $talent = Talent::factory()->create(['slug' => 'side-bar-talent', 'display_name' => 'Side Bar']);

    $response = $this->actingAs($talent, 'talent')->get(route('talent.projects'));

    $response->assertOk()
        // The html classes are set before paint (folded by default, remembered per browser).
        ->assertSee("localStorage.getItem('fama-side-folded')", false)
        ->assertSee('class="fx-side"', false)
        // 13 Nov 2026 review: the portfolio first, then the feed (Community).
        ->assertSeeInOrder(['Profile', 'Portfolio', 'Projects', 'View profile', 'Community', 'Feed', 'Activity', 'Credits', 'Notifications'])
        // The folded rail is icons only: each link carries its name as a tooltip.
        ->assertSee('data-tip="Projects"', false)
        // Create → a project (the editor) or a post (the feed opens its composer).
        ->assertSee(route('talent.projects.new'), false)
        ->assertSee(route('feed', ['new' => 'post']), false)
        ->assertSee('View your profile')
        ->assertSee(route('notifications.page'), false);
});

it('orders the top bar links Portfolio · Projects · Feed · Activity · Credits (owner 2026-11-25)', function () {
    $talent = Talent::factory()->create(['slug' => 'top-bar-talent']);

    $html = $this->actingAs($talent, 'talent')->get(route('talent.credits'))->assertOk()->getContent();

    preg_match('#<ul class="fx-links">(.*?)</ul>#s', $html, $m);
    expect($m[1] ?? '')->toMatch('#>Portfolio<.*Projects.*Feed.*Activity.*Credits#s')
        ->not->toContain('Profile structure');
});

it('gives visitors the top bar with Log in and Join, and no side bar', function () {
    $this->get(route('login'))->assertOk();

    $this->get(route('feed'))
        ->assertOk()
        ->assertSee('class="fx-join"', false)
        ->assertSee(route('register'), false)
        ->assertDontSee('class="fx-side"', false)
        ->assertDontSee('fama-side-folded', false);
});

it('pins the Private notice while the profile is Private, except on Edit profile (ADR-AC)', function () {
    $talent = Talent::factory()->create(['slug' => 'private-notice', 'is_published' => false]);

    $this->actingAs($talent, 'talent')->get(route('talent.projects'))
        ->assertOk()
        ->assertSee('class="fx-notice"', false)
        ->assertSee('Complete your profile')
        ->assertDontSee('under review');

    $this->actingAs($talent, 'talent')->get(route('talent.profile.edit'))
        ->assertOk()
        ->assertDontSee('class="fx-notice"', false);
});

it('shows no notice once the profile is Live', function () {
    $talent = Talent::factory()->create(['slug' => 'live-notice', 'is_published' => true]);

    $this->actingAs($talent, 'talent')->get(route('talent.projects'))
        ->assertOk()
        ->assertDontSee('class="fx-notice"', false);
});
