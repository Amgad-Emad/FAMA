<?php

namespace Database\Seeders;

use App\Actions\Catalog\SyncSkillCatalog;
use Illuminate\Database\Seeder;

/**
 * Seeds the skills catalog — the prototype's 41 skills (ADR-AF; before that
 * nine, ADR-Y). The data lives in database/data/skill-catalog.php and is
 * applied by SyncSkillCatalog, which the catalog migration also runs, so a
 * fresh database and an upgraded one hold the same rows. Skills are named as
 * the DISCIPLINE (ADR-S); `category` stays the model|crew|creative enum and
 * gates block eligibility; `default_blocks` seeds a new skill tab.
 * Idempotent on `slug`. Specialities follow in SpecialitySeeder.
 */
class TalentTypeSeeder extends Seeder
{
    /**
     * Create or rename every catalog skill.
     */
    public function run(SyncSkillCatalog $sync): void
    {
        $sync->skills();
    }
}
