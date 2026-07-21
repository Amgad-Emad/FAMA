<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Admin RBAC (ADR-H, spatie/laravel-permission on the `admin` guard). Seeds the
 * granular admin permissions and the three staff roles, then grants the demo
 * admin super-admin. Idempotent via findOrCreate.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    private const GUARD = 'admin';

    private const PERMISSIONS = [
        'manage-flows',       // author contract_flows / contract_flow_steps
        'manage-blocks',      // govern the block_types catalog
        'moderate-content',   // approve/reject reviews, profiles, media
        'intervene-contracts',    // override / advance contract steps
        'manage-settings',    // tune platform globals
        'manage-users',       // manage admin staff
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, self::GUARD);
        }

        // Rebuild the cache AFTER creating permissions so syncPermissions resolves them.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findOrCreate('super-admin', self::GUARD)->syncPermissions(self::PERMISSIONS);
        Role::findOrCreate('moderator', self::GUARD)->syncPermissions(['moderate-content', 'intervene-contracts']);
        Role::findOrCreate('support', self::GUARD)->syncPermissions(['moderate-content']);

        User::where('email', 'admin-demo@fama.test')->first()?->assignRole('super-admin');
    }
}
