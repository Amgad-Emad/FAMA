<?php

namespace App\Actions;

use App\Actions\Contracts\Action;
use App\Models\BlockType;
use App\Models\ProfileBlock;
use App\Models\Talent;
use App\States\TalentProfile\Created;
use App\States\TalentProfile\Draft;
use Illuminate\Support\Collection;

/**
 * Seed a talent's profile_blocks from the merged default blocks of their linked
 * types (talent-spec workflow #10/#11). Idempotent: a default block already on the
 * profile is skipped, so calling this again when a profession is added only seeds
 * the *missing* blocks (the `is_repeatable` flag governs manual adds via
 * ProfileBlockService, not default seeding). A freshly-created profile moves
 * Created → Draft once blocks exist.
 */
class SeedProfileBlocks implements Action
{
    public function __construct(private readonly MergeDefaultBlocksForTypes $mergeDefaults) {}

    /**
     * @return Collection<int, ProfileBlock> The blocks created this call.
     */
    public function __invoke(Talent $talent): Collection
    {
        $keys = ($this->mergeDefaults)($talent->talentTypes);

        $blockTypes = BlockType::whereIn('key', $keys)->get()->keyBy('key');
        $presentTypeIds = $talent->profileBlocks()->pluck('block_type_id')->all();

        $position = $talent->profileBlocks()->exists()
            ? ((int) $talent->profileBlocks()->max('position')) + 1
            : 0;

        $created = new Collection;

        foreach ($keys as $key) {
            $blockType = $blockTypes->get($key);

            if ($blockType === null) {
                continue;
            }

            // Skip any default block already on the profile (seed each once).
            if (in_array($blockType->id, $presentTypeIds, true)) {
                continue;
            }

            $created->push($talent->profileBlocks()->create([
                'block_type_id' => $blockType->id,
                'title' => $blockType->getTranslations('name'),
                'position' => $position++,
                'is_visible' => true,
                'status' => 'visible',
                'layout' => $blockType->default_layout,
                'settings' => [],
                'content' => null,
            ]));

            $presentTypeIds[] = $blockType->id;
        }

        if ($talent->status->equals(Created::class)) {
            $talent->status->transitionTo(Draft::class);
        }

        return $created;
    }
}
