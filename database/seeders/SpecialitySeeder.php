<?php

namespace Database\Seeders;

use App\Actions\Catalog\SyncSkillCatalog;
use Illuminate\Database\Seeder;

/**
 * Seeds the controlled speciality vocabulary — the second and final level of
 * the skills tree (ADR-Y), now the prototype's lists for all 41 skills
 * (ADR-AF, database/data/skill-catalog.php). Must run AFTER TalentTypeSeeder;
 * idempotent on (talent_type_id, slug). An entry the catalog drops is retired
 * (is_active = false), never deleted.
 *
 * The rules the vocabulary follows: one axis (what kind of SUBJECT — output
 * format belongs to the project); a phrase a brand would type; overlap across
 * skills is fine ("Fashion" under several), which is what lets one search
 * assemble a crew.
 */
class SpecialitySeeder extends Seeder
{
    /**
     * Sync every catalog skill's specialities.
     */
    public function run(SyncSkillCatalog $sync): void
    {
        $sync->specialities();
    }
}
