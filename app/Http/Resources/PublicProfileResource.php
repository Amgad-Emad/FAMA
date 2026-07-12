<?php

namespace App\Http\Resources;

use App\Models\Talent;
use App\Models\TalentType;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Public talent profile — the two-region shape (talent-spec, ADR-R): `identity`
 * (header + universal/meta + pricing rate + skills), `universal_blocks`
 * (`talent_type_id = NULL`) and `skills[]` each carrying their own `blocks[]`.
 *
 * This is the contract the mobile API (Phase 4) will return; the web page renders
 * the same regions server-side + lazy tabs. Assumes the profile is eager-loaded
 * (talentTypes, visible profileBlocks, content) — no N+1.
 *
 * @mixin Talent
 */
class PublicProfileResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Talent $talent */
        $talent = $this->resource;

        $skills = $talent->talentTypes
            ->sortBy(fn (TalentType $t) => [$t->pivot->is_primary ? 0 : 1, (int) $t->pivot->position])
            ->values();
        $primary = $skills->firstWhere(fn (TalentType $t) => (bool) $t->pivot->is_primary) ?? $skills->first();

        return [
            'identity' => [
                'slug' => $talent->slug,
                'display_name' => $talent->display_name,
                'headline' => $talent->getTranslations('headline'),
                'bio' => $talent->getTranslations('bio'),
                'avatar_url' => $talent->avatar_url,
                'base_city' => $talent->base_city,
                'base_country' => $talent->base_country,
                'view_count' => (int) $talent->view_count,
                'projects_count' => $talent->projects->count(),
                'rating' => $talent->reviews->count() ? round((float) $talent->reviews->avg('rating'), 1) : null,
                'pricing_rate' => $this->pricingRate($talent),
                'primary_skill' => $primary?->slug,
                'skills' => $skills->map(fn (TalentType $s) => [
                    'id' => $s->id,
                    'slug' => $s->slug,
                    'name' => $s->getTranslations('name'),
                    'category' => $s->category,
                    'is_primary' => (bool) $s->pivot->is_primary,
                ])->all(),
            ],
            'universal_blocks' => ProfileBlockResource::collection($this->visibleBlocks($talent, null)),
            'skills' => $skills->map(fn (TalentType $s) => [
                'id' => $s->id,
                'slug' => $s->slug,
                'name' => $s->getTranslations('name'),
                'blocks' => ProfileBlockResource::collection($this->visibleBlocks($talent, $s->id)),
            ])->values()->all(),
        ];
    }

    /**
     * A scope's visible blocks in position order (hero is the header — skipped).
     *
     * @return Collection<int, \App\Models\ProfileBlock>
     */
    private function visibleBlocks(Talent $talent, ?int $typeId): Collection
    {
        return $talent->profileBlocks
            ->where('talent_type_id', $typeId)
            ->filter(fn ($block) => $block->is_visible && $block->blockType->key !== 'hero')
            ->sortBy('position')
            ->values();
    }

    /**
     * The indicative pricing rate (ADR-N), or null when not fully set.
     *
     * @return array<string, mixed>|null
     */
    private function pricingRate(Talent $talent): ?array
    {
        if (blank($talent->rate_amount) || blank($talent->rate_unit) || blank($talent->rate_currency)) {
            return null;
        }

        return ['unit' => $talent->rate_unit, 'amount' => $talent->rate_amount, 'currency' => $talent->rate_currency];
    }
}
