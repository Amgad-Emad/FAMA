<?php

use App\Models\Talent;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

/** Moderation's View panel embeds the public profile — staff may see private ones. */
beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->talent = Talent::factory()->draft()->unverified()->create(['is_published' => false]);
});

it('hides a private profile from visitors', function () {
    $this->get('/'.$this->talent->slug)->assertNotFound();
});

it('shows a private profile to a moderator, with the staff strip and no visitor nav', function () {
    $admin = User::factory()->create();
    $admin->assignRole('moderator');
    $before = (int) $this->talent->view_count;

    $this->actingAs($admin, 'admin')->get('/'.$this->talent->slug.'?staff_preview=1')
        ->assertOk()
        ->assertSee('Staff preview')
        ->assertDontSee('class="fx-nav"', false);

    expect((int) $this->talent->fresh()->view_count)->toBe($before); // staff visits are not views
});

it('still hides it from an admin without moderate-content', function () {
    $this->actingAs(User::factory()->create(), 'admin')->get('/'.$this->talent->slug)->assertNotFound();
});
