<?php

namespace Database\Seeders;

use App\Models\ContractTemplate;
use Illuminate\Database\Seeder;

/**
 * Seeds the default "Fama Standard" contract template — the standard-terms schema
 * (50% deposit held by Fama, 50% forfeit, 1 revision) + the six canonical clauses.
 * Runs before ContractFlowSeeder so the standard flow can link to it (ADR-V).
 */
class ContractTemplateSeeder extends Seeder
{
    public function run(): void
    {
        ContractTemplate::query()->updateOrCreate(
            ['slug' => 'fama-standard'],
            [
                'name' => 'Fama Standard',
                'description' => 'The default brand ↔ talent contract terms.',
                'applies_to' => null,
                'is_active' => true,
                'is_default' => true,
                'deposit_pct' => 50,
                'deposit_holder' => 'Fama',
                'cancellation_forfeit_pct' => 50,
                'revision_rounds' => 1,
                'preview_currency' => 'EGP',
                'intro' => 'Between {brand} and {talent}',
                'clauses' => ContractTemplate::defaultClauses(),
            ],
        );
    }
}
