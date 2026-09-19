<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PROFILE-LEVEL default blocks (ADR-Z).
 *
 * `talent_types.default_blocks` seeds into ONE SKILL'S TAB (ADR-Q) — that is
 * right for a gallery or an equipment list, which differ per discipline. It is
 * wrong for Recognitions and Willing to explore: those describe the person, not
 * a discipline, so rendering them once per skill tab would repeat the same
 * content two or three times down the page.
 *
 * Rather than hardcode a list of keys in the seeding action, this adds
 * `block_types.is_profile_default` so the answer is DATA the admin catalog can
 * govern (ADR-T's principle), not code. A block flagged here is seeded ONCE at
 * `talent_type_id = NULL` — the universal/profile-level region.
 *
 * Also inserts the two new block types themselves, so an existing database needs
 * `migrate` alone; BlockTypeSeeder seeds the same rows on a fresh one, which
 * makes those inserts a no-op there.
 */
return new class extends Migration
{
    /**
     * The new catalog entries, in the order they should appear.
     *
     * @var list<array<string, mixed>>
     */
    private array $blockTypes = [
        [
            'key' => 'recognitions', 'en' => 'Recognitions', 'ar' => 'تزكيات الزملاء',
            'layout' => 'list',
        ],
        [
            'key' => 'interests', 'en' => 'Willing to explore', 'ar' => 'منفتح على',
            'layout' => 'list',
        ],
    ];

    public function up(): void
    {
        Schema::table('block_types', function (Blueprint $table): void {
            $table->boolean('is_profile_default')->default(false)->after('is_repeatable');
        });

        $position = (int) DB::table('block_types')->max('position');

        foreach ($this->blockTypes as $entry) {
            $position++;

            if (DB::table('block_types')->where('key', $entry['key'])->exists()) {
                DB::table('block_types')->where('key', $entry['key'])
                    ->update(['is_profile_default' => true, 'updated_at' => now()]);

                continue;
            }

            DB::table('block_types')->insert([
                'key' => $entry['key'],
                'name' => json_encode(['en' => $entry['en'], 'ar' => $entry['ar']], JSON_UNESCAPED_UNICODE),
                'icon' => 'lucide-'.str_replace('_', '-', $entry['key']),
                'availability' => 'universal',
                'content_source' => 'table',
                'default_layout' => $entry['layout'],
                'is_active' => true,
                'is_repeatable' => false,
                'is_profile_default' => true,
                'position' => $position,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->backfillExistingProfiles();
    }

    /**
     * Give every talent that already has blocks the two new profile-level ones,
     * appended to the universal region. Without this the sections would only
     * ever appear for talents created after the deploy — and V1 removed the
     * block picker, so nobody could add them by hand.
     */
    private function backfillExistingProfiles(): void
    {
        $newTypes = DB::table('block_types')
            ->whereIn('key', array_column($this->blockTypes, 'key'))
            ->get(['id', 'key', 'name', 'default_layout']);

        if ($newTypes->isEmpty()) {
            return;
        }

        DB::table('talents')->orderBy('id')->select('id')->chunk(200, function ($talents) use ($newTypes): void {
            foreach ($talents as $talent) {
                $existing = DB::table('profile_blocks')
                    ->where('talent_id', $talent->id)
                    ->pluck('block_type_id')
                    ->all();

                // Skip a talent with no blocks at all: their profile has never
                // been seeded, and adding two orphan blocks would leave them in
                // a state the seeding action never produces.
                if ($existing === []) {
                    continue;
                }

                $position = (int) DB::table('profile_blocks')
                    ->where('talent_id', $talent->id)
                    ->whereNull('talent_type_id')
                    ->max('position');

                foreach ($newTypes as $type) {
                    if (in_array($type->id, $existing, true)) {
                        continue;
                    }

                    $position++;

                    DB::table('profile_blocks')->insert([
                        'talent_id' => $talent->id,
                        'block_type_id' => $type->id,
                        'talent_type_id' => null, // the universal / profile-level region
                        'title' => $type->name,
                        'position' => $position,
                        'is_visible' => true,
                        'status' => 'visible',
                        'layout' => $type->default_layout,
                        'settings' => json_encode([]),
                        'content' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('block_types', function (Blueprint $table): void {
            $table->dropColumn('is_profile_default');
        });

        // The block types themselves are NOT deleted: by the time anyone rolls
        // this back, talents hold placements of them, and deleting the catalog
        // rows would cascade those away.
    }
};
