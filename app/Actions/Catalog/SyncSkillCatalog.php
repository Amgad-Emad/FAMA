<?php

namespace App\Actions\Catalog;

use App\Models\Speciality;
use App\Models\TalentType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Brings the skills + specialities catalog in line with
 * `database/data/skill-catalog.php` (the prototype's 41 skills — ADR-AF).
 * Idempotent: the seeders run it on a fresh database and the catalog
 * migration runs it on an existing one, so both end with the same rows.
 *
 * What it never does (the catalog rules, ADR-Y / ADR-T):
 *  - change a slug that exists — a skill is matched by its slug or one of its
 *    `legacy` slugs and renamed IN PLACE (IDs, claims and projects survive);
 *  - delete a speciality — one the catalog no longer lists is RETIRED
 *    (is_active = false): gone from every picker, still rendering for the
 *    talents and projects that claimed it.
 */
class SyncSkillCatalog
{
    /**
     * Sync skills, then their specialities, in one transaction.
     */
    public function __invoke(): void
    {
        DB::transaction(function (): void {
            $this->skills();
            $this->specialities();
        });
    }

    /**
     * The catalog as data (one source, read fresh each call).
     *
     * @return list<array<string, mixed>>
     */
    public static function catalog(): array
    {
        return require database_path('data/skill-catalog.php');
    }

    /**
     * Create or rename every skill (matched by slug, then legacy slugs).
     */
    public function skills(): void
    {
        foreach (self::catalog() as $skill) {
            $type = TalentType::query()
                ->whereIn('slug', array_merge([$skill['slug']], $skill['legacy'] ?? []))
                ->orderByRaw('slug = ? desc', [$skill['slug']])
                ->first() ?? new TalentType;

            $type->fill([
                'slug' => $skill['slug'],
                'name' => ['en' => $skill['en'], 'ar' => $skill['ar']],
                'category' => $skill['category'],
                'default_blocks' => $skill['default_blocks'],
                'icon' => 'lucide-'.$skill['slug'],
                'description' => ['en' => $skill['en'].' on Fama.', 'ar' => $skill['ar'].' على فاما.'],
            ])->save();
        }
    }

    /**
     * Each skill's specialities, in catalog order. A listed entry with a
     * keep-slug updates that existing row; the rest are matched on
     * Str::slug(en). Unlisted entries under a catalog skill are retired.
     */
    public function specialities(): void
    {
        $types = TalentType::query()->pluck('id', 'slug');

        foreach (self::catalog() as $skill) {
            $typeId = $types[$skill['slug']] ?? null;
            if ($typeId === null) {
                continue;
            }

            $kept = [];
            foreach ($skill['specialities'] as $position => $entry) {
                [$en, $ar] = $entry;
                $slug = isset($entry[2]) ? $entry[2] : Str::slug($en);
                $kept[] = $slug;

                Speciality::query()->updateOrCreate(
                    ['talent_type_id' => $typeId, 'slug' => $slug],
                    [
                        'name' => ['en' => $en, 'ar' => $ar],
                        'is_regional' => (bool) ($entry[3] ?? false),
                        'is_active' => true,
                        'position' => $position,
                    ],
                );
            }

            Speciality::query()
                ->where('talent_type_id', $typeId)
                ->whereNotIn('slug', $kept)
                ->update(['is_active' => false]);
        }
    }
}
