<?php

use App\Models\Brand;
use App\Models\Review;
use App\Models\Talent;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * An admin token carrying the guard ability plus the admin's spatie permissions.
 */
function adminToken(string $role): string
{
    $admin = User::factory()->create();
    $admin->assignRole($role);
    $abilities = array_values(array_unique(['admin', ...$admin->getAllPermissions()->pluck('name')->all()]));

    return $admin->createToken('t', $abilities)->plainTextToken;
}

it('requires an admin token for the overview', function () {
    $this->getJson('/api/v1/admin/overview')->assertUnauthorized();
});

it('rejects a talent token on admin endpoints', function () {
    $talentToken = Talent::factory()->create()->createToken('t', ['talent'])->plainTextToken;

    api()->withToken($talentToken)->getJson('/api/v1/admin/overview')->assertForbidden();
});

it('returns the governance overview counts for an admin', function () {
    Talent::factory()->count(2)->create();
    Brand::factory()->create();
    Review::factory()->pending()->create();

    api()->withToken(adminToken('super-admin'))->getJson('/api/v1/admin/overview')
        ->assertOk()
        ->assertJsonPath('data.pending_reviews', 1)
        ->assertJsonStructure(['data' => ['flows', 'active_deals', 'awaiting_admin', 'talents', 'brands']]);
});

it('gates the activity feed on manage-settings', function () {
    // super-admin has manage-settings.
    api()->withToken(adminToken('super-admin'))->getJson('/api/v1/admin/activity')
        ->assertOk()->assertJsonStructure(['data', 'meta' => ['pagination']]);

    // support lacks manage-settings → 403.
    api()->withToken(adminToken('support'))->getJson('/api/v1/admin/activity')->assertForbidden();
});
