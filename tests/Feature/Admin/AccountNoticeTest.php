<?php

use App\Mail\AccountStatusNotice;
use App\Models\Talent;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Mail;

/**
 * Verify / Suspend open an email composer: suggested copy + a preview of the
 * real email; the decision can carry the edited email or go out silently.
 */
beforeEach(function () {
    Mail::fake();
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
    $this->talent = Talent::factory()->draft()->unverified()->create(['display_name' => 'Nour', 'email' => 'nour@example.com']);
});

it('suggests the email and renders its preview', function () {
    $this->actingAs($this->admin, 'admin')
        ->postJson("/admin/moderation/talents/{$this->talent->id}/notice/verify")
        ->assertOk()
        ->assertJsonPath('data.defaults.email', 'nour@example.com')
        ->assertJsonPath('data.defaults.cta_url', route('talent.profile.edit'))
        ->assertJson(fn ($json) => $json->where('data.html', fn ($html) => str_contains($html, 'Nour'))->etc());
});

it('previews the edited copy', function () {
    $html = $this->actingAs($this->admin, 'admin')
        ->postJson("/admin/moderation/talents/{$this->talent->id}/notice/suspend", ['heading' => 'A custom heading'])
        ->assertOk()->json('data.html');

    expect($html)->toContain('A custom heading');
});

it('verifies and sends the edited email', function () {
    $this->actingAs($this->admin, 'admin')
        ->patchJson("/admin/moderation/talents/{$this->talent->id}/verify", ['notify' => [
            'send' => true, 'email' => 'nour@example.com', 'subject' => 'You are in',
            'heading' => 'Checked!', 'message' => "Hello.\n\nGo Live.",
        ]])->assertOk();

    expect((bool) $this->talent->fresh()->is_verified)->toBeTrue();
    Mail::assertQueued(AccountStatusNotice::class, fn ($m) => $m->hasTo('nour@example.com')
        && $m->subjectLine === 'You are in' && $m->heading === 'Checked!' && $m->type === 'verify');
});

it('takes the decision without an email when sending is unticked', function () {
    $this->actingAs($this->admin, 'admin')
        ->patchJson("/admin/moderation/talents/{$this->talent->id}/suspend", ['notify' => ['send' => false]])
        ->assertOk();

    expect($this->talent->fresh()->status->getValue())->toBe('suspended');
    Mail::assertNothingQueued();
});

it('rejects a bad address before the decision runs', function () {
    $this->actingAs($this->admin, 'admin')
        ->patchJson("/admin/moderation/talents/{$this->talent->id}/verify", ['notify' => [
            'send' => true, 'email' => 'not-an-email', 'subject' => 'x', 'heading' => 'x', 'message' => 'x',
        ]])->assertUnprocessable()->assertJsonValidationErrors('notify.email');

    expect((bool) $this->talent->fresh()->is_verified)->toBeFalse();
    Mail::assertNothingQueued();
});

it('keeps the old silent verify working (no notify field)', function () {
    $this->actingAs($this->admin, 'admin')
        ->patchJson("/admin/moderation/talents/{$this->talent->id}/verify")->assertOk();

    Mail::assertNothingQueued();
});
