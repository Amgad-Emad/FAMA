<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

// The global confirmation dialog is rendered once per dashboard layout, and
// destructive buttons are wired to $confirm before their action runs.

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
});

it('renders the confirm dialog + wires destructive actions on the admin console', function () {
    $html = $this->actingAs($this->admin, 'admin')->get('/admin/users')->assertOk()->getContent();

    // The teleported dialog is present…
    expect($html)->toContain('x-teleport="body"');
    // …and the Remove button opens it before removing (not a bare handler).
    expect($html)->toContain('$confirm(');
    expect($html)->toContain('remove(user))');
    expect($html)->not->toMatch('/@click="remove\(user\)"/');
});

it('gates project cancel + account delete behind the dialog in moderation', function () {
    $html = $this->actingAs($this->admin, 'admin')->get('/admin/moderation')->assertOk()->getContent();

    expect($html)->toContain('Cancel this project?');
    expect($html)->toContain('Delete this account?');
    // No un-gated delete/cancel handlers remain.
    expect($html)->not->toMatch("/@click=\"action\((row|detail),'(delete|cancel)'\)\"/");
});
