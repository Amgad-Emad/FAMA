<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Skill-scoped blocks & projects (ADR-Q). A talent's `profile_blocks` and
 * `projects` now belong to a skill (`talent_type_id`, nullable):
 *   - NULL     → a profile-level / universal item (rendered above the tabs).
 *   - NOT NULL → the item lives in that skill's tab.
 * `profile_blocks.position` is now ordered WITHIN a scope (per talent_type_id).
 *
 * Backfill:
 *   - projects → the talent's PRIMARY skill.
 *   - profile_blocks → if the block's type is gated (by_type / by_category) and
 *     matches exactly ONE of the talent's skills, stamp that skill; universal or
 *     ambiguous blocks stay NULL (profile-level).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profile_blocks', function (Blueprint $table) {
            $table->foreignId('talent_type_id')->nullable()->after('block_type_id')
                ->constrained('talent_types')->nullOnDelete();
            $table->index(['talent_id', 'talent_type_id', 'position'], 'profile_blocks_scope_position_index');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('talent_type_id')->nullable()->after('talent_id')
                ->constrained('talent_types')->nullOnDelete();
            $table->index('talent_type_id');
        });

        $projects = $this->backfillProjects();
        [$blocksStamped, $blocksNull] = $this->backfillProfileBlocks();

        $this->report($projects, $blocksStamped, $blocksNull);
    }

    public function down(): void
    {
        Schema::table('profile_blocks', function (Blueprint $table) {
            $table->dropIndex('profile_blocks_scope_position_index');
            $table->dropForeign(['talent_type_id']);
            $table->dropColumn('talent_type_id');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['talent_type_id']);
            $table->dropIndex(['talent_type_id']);
            $table->dropColumn('talent_type_id');
        });
    }

    /**
     * Stamp each existing project with its talent's primary skill. Returns the count.
     * (Public so the backfill is unit-testable against legacy rows.)
     */
    public function backfillProjects(): int
    {
        $stamped = 0;

        DB::table('projects')->orderBy('id')->chunkById(500, function ($rows) use (&$stamped) {
            foreach ($rows as $row) {
                $primary = DB::table('talent_talent_type')
                    ->where('talent_id', $row->talent_id)
                    ->where('is_primary', true)
                    ->value('talent_type_id');

                if ($primary !== null) {
                    DB::table('projects')->where('id', $row->id)->update(['talent_type_id' => $primary]);
                    $stamped++;
                }
            }
        });

        return $stamped;
    }

    /**
     * Backfill profile_blocks: stamp gated blocks that match exactly one of the
     * talent's skills; leave universal/ambiguous blocks NULL. Returns [stamped, null].
     *
     * @return array{0:int,1:int}
     */
    public function backfillProfileBlocks(): array
    {
        $blockTypes = DB::table('block_types')->get()->keyBy('id');
        $catsByBlockType = DB::table('block_type_category')->get()->groupBy('block_type_id');
        $typesByBlockType = DB::table('block_type_talent_type')->get()->groupBy('block_type_id');

        $stamped = 0;
        $left = 0;

        DB::table('profile_blocks')->orderBy('id')->chunkById(500, function ($rows) use (&$stamped, &$left, $blockTypes, $catsByBlockType, $typesByBlockType) {
            foreach ($rows as $block) {
                $type = $blockTypes->get($block->block_type_id);
                $skills = DB::table('talent_talent_type')
                    ->join('talent_types', 'talent_types.id', '=', 'talent_talent_type.talent_type_id')
                    ->where('talent_talent_type.talent_id', $block->talent_id)
                    ->get(['talent_types.id', 'talent_types.category']);

                $match = null;

                if ($type !== null && $type->availability === 'by_category') {
                    $cats = ($catsByBlockType->get($block->block_type_id) ?? collect())->pluck('category');
                    $eligible = $skills->filter(fn ($s) => $cats->contains($s->category));
                    $match = $eligible->count() === 1 ? $eligible->first()->id : null;
                } elseif ($type !== null && $type->availability === 'by_type') {
                    $gate = ($typesByBlockType->get($block->block_type_id) ?? collect())->pluck('talent_type_id');
                    $eligible = $skills->filter(fn ($s) => $gate->contains($s->id));
                    $match = $eligible->count() === 1 ? $eligible->first()->id : null;
                }

                if ($match !== null) {
                    DB::table('profile_blocks')->where('id', $block->id)->update(['talent_type_id' => $match]);
                    $stamped++;
                } else {
                    $left++;
                }
            }
        });

        return [$stamped, $left];
    }

    private function report(int $projects, int $blocksStamped, int $blocksNull): void
    {
        if (! app()->runningInConsole() || app()->runningUnitTests()) {
            return;
        }

        fwrite(STDOUT, PHP_EOL
            ."  Skill-scope backfill: projects stamped to primary skill = {$projects}; "
            ."profile_blocks stamped to a skill = {$blocksStamped}, left profile-level (NULL) = {$blocksNull}.".PHP_EOL);
    }
};
