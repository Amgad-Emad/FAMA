<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database: the admin user + its RBAC, the talent-side
     * catalogs (skills + block catalog), the contract flow + platform settings, then
     * the rich demo talent/brand/admin data.
     *
     * Order matters: RolesAndPermissionsSeeder grants the demo super-admin the
     * permissions AdminDemoSeeder authorizes against; SettingsSeeder runs after
     * ContractFlowSeeder so `default_contract_flow_id` can point at the default flow.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin Demo',
            'email' => 'admin-demo@fama.test',
        ]);

        $this->call([
            RolesAndPermissionsSeeder::class,
            TalentTypeSeeder::class,
            BlockTypeSeeder::class,
            ContractFlowSeeder::class,
            SettingsSeeder::class,
            TalentDemoSeeder::class,
            TalentShowcaseSeeder::class,
            BrandDemoSeeder::class,
            AdminDemoSeeder::class,
        ]);
    }
}
