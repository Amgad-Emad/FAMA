<?php

namespace App\Actions;

use App\Actions\Contracts\Action;
use App\Models\TalentType;
use Illuminate\Support\Collection;

/**
 * Merge the `default_blocks` of every linked talent type into one ordered,
 * de-duplicated list of block-type keys. A multi-type talent (e.g. model +
 * photographer) gets each block once, in first-seen order (talent-spec §1).
 */
class MergeDefaultBlocksForTypes implements Action
{
    /**
     * @param  iterable<TalentType>  $types
     * @return list<string> Ordered, de-duplicated block-type keys.
     */
    public function __invoke(iterable $types): array
    {
        return Collection::make($types)
            ->flatMap(fn (TalentType $type): array => $type->default_blocks ?? [])
            ->unique()
            ->values()
            ->all();
    }
}
