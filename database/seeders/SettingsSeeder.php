<?php

namespace Database\Seeders;

use App\Models\DealFlow;
use App\Services\SettingsService;
use Illuminate\Database\Seeder;

/**
 * Seeds the admin-tunable platform globals (schema-master §6). Runs after
 * DealFlowSeeder so `default_deal_flow_id` can point at the seeded default flow.
 */
class SettingsSeeder extends Seeder
{
    public function run(SettingsService $settings): void
    {
        $defaultFlow = DealFlow::query()->where('is_default', true)->first()
            ?? DealFlow::query()->where('is_active', true)->first();

        $settings->setMany([
            'default_currency' => 'EGP',
            'default_deal_flow_id' => $defaultFlow?->id,
            'feature_flags' => [
                'brand_initiated_deals' => false,
                'public_brand_pages' => true,
                'talent_discovery' => true,
            ],
        ]);
    }
}
