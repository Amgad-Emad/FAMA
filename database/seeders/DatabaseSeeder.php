<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database: an admin user, the talent-side catalogs
     * (professions + block catalog), then the rich multi-type demo talent.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            TalentTypeSeeder::class,
            BlockTypeSeeder::class,
            DealFlowSeeder::class,
            TalentDemoSeeder::class,
            TalentShowcaseSeeder::class,
            BrandDemoSeeder::class,
        ]);
    }
}
