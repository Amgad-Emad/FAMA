<?php

use App\Actions\Catalog\SyncSkillCatalog;
use App\Models\Speciality;
use App\Models\Talent;
use App\Models\TalentType;
use App\Services\SkillsService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Skills catalog 9 → 41, identical to the prototype (ADR-AF). Runs in place so a
 * deploy needs `migrate` alone; on a fresh (empty) database it does nothing and the
 * seeders produce the same rows.
 *
 *  1. `projects` and `showreel` become eligible for the `model` category — the
 *     on-camera skills the catalog adds (Acting, Voice, Presenting, Dance,
 *     Content creation) need them, and the prototype gives every skill a
 *     projects tab.
 *  2. SyncSkillCatalog renames the nine existing skills in place (IDs and
 *     claims survive) and adds the other 32 with their specialities;
 *     specialities the catalog no longer lists are RETIRED, not deleted.
 *  3. "Hair & makeup" splits in two. The row becomes Makeup (slug
 *     `hair-makeup` → `makeup`: the meaning changed, so the old deep link goes
 *     with it), every talent who held it ALSO gets Hair (they claimed hair), and
 *     the old "Hair styling" speciality moves under Hair, retired, so their
 *     claim keeps rendering in the right tab.
 */
return new class extends Migration
{
    /**
     * Apply the catalog.
     */
    public function up(): void
    {
        // A fresh database has no seeded catalog yet (only the three rows the
        // 2026_08_22 migration inserts) — the seeders build it, with the same data.
        if (! DB::table('talent_types')->where('slug', 'photography')->exists()) {
            return;
        }

        // 1. Block eligibility for the on-camera category.
        $blockIds = DB::table('block_types')->whereIn('key', ['projects', 'showreel'])->pluck('id');
        foreach ($blockIds as $blockId) {
            $exists = DB::table('block_type_category')->where(['block_type_id' => $blockId, 'category' => 'model'])->exists();
            if (! $exists) {
                DB::table('block_type_category')->insert(['block_type_id' => $blockId, 'category' => 'model']);
            }
        }

        // Who held the combined skill, captured before the rename.
        $hairMakeupId = TalentType::query()->where('slug', 'hair-makeup')->value('id');
        $holders = $hairMakeupId
            ? DB::table('talent_talent_type')->where('talent_type_id', $hairMakeupId)->pluck('talent_id')->all()
            : [];

        // 2. The catalog.
        app(SyncSkillCatalog::class)();

        if (! $hairMakeupId) {
            return;
        }

        // 3. The split.
        $hair = TalentType::query()->where('slug', 'hair')->firstOrFail();
        Speciality::query()
            ->where('talent_type_id', $hairMakeupId)
            ->where('slug', 'hair-styling')
            ->update(['talent_type_id' => $hair->id, 'is_active' => false]);

        $skills = app(SkillsService::class);
        Talent::query()->whereIn('id', $holders)->with('talentTypes')->each(function (Talent $talent) use ($hair, $skills): void {
            if (! $talent->talentTypes->contains('id', $hair->id)) {
                $skills->addType($talent, $hair);
            }
        });
    }

    /**
     * Not reversible: the catalog is data talents and projects now point at.
     */
    public function down(): void
    {
        // Intentionally empty — rolling back would orphan claims on the 32 new skills.
    }
};
