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
 * (talentTypes, specialities, visible profileBlocks, content) — no N+1.
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

        // Claimed specialities, bucketed by skill once (ADR-Y) so neither the
        // identity block nor the skills list re-groups per row.
        $specialities = $talent->relationLoaded('specialities')
            ? $talent->specialities->groupBy('talent_type_id')
            : collect();

        return [
            'identity' => [
                'slug' => $talent->slug,
                'display_name' => $talent->display_name,
                'headline' => $talent->getTranslations('headline'),
                'bio' => $talent->getTranslations('bio'),
                'avatar_url' => $talent->avatar_url,
                'base_city' => $talent->base_city,
                'base_country' => $talent->base_country,
                // NOW (ADR-Z). The stamp always rides with the flag: an
                // availability toggle without a date looks current forever.
                'now' => [
                    'is_available' => (bool) $talent->is_available,
                    'will_travel' => (bool) $talent->will_travel,
                    'availability_changed_at' => $talent->availability_changed_at?->toIso8601String(),
                ],
                'worldview' => $talent->getTranslations('worldview'),
                'social' => $talent->relationLoaded('socialHandles')
                    ? $talent->socialHandles->map(fn ($h) => [
                        'platform' => $h->platform, 'handle' => $h->handle, 'url' => $h->url,
                    ])->values()->all()
                    : [],
                'view_count' => (int) $talent->view_count,
                // Own projects + accepted credits, de-duplicated (ADR-AA).
                'projects_count' => $talent->projects->pluck('id')
                    ->merge($talent->relationLoaded('creditedProjects') ? $talent->creditedProjects->pluck('id') : [])
                    ->unique()->count(),
                'rating' => $talent->reviews->count() ? round((float) $talent->reviews->avg('rating'), 1) : null,
                'pricing_rate' => $this->pricingRate($talent),
                'primary_skill' => $primary?->slug,
                'skills' => $skills->map(fn (TalentType $s) => [
                    'id' => $s->id,
                    'slug' => $s->slug,
                    'name' => $s->getTranslations('name'),
                    'category' => $s->category,
                    'is_primary' => (bool) $s->pivot->is_primary,
                    'specialities' => SpecialityResource::collection($specialities->get($s->id, collect())),
                ])->all(),
            ],
            // Peer vouches and stated directions are profile-level sections, so
            // they sit beside the blocks rather than inside a skill (ADR-Z).
            'recognitions' => $talent->relationLoaded('recognitions')
                ? RecognitionResource::collection($talent->recognitions->where('is_approved', true)->values())
                : [],
            'interests' => $talent->relationLoaded('interests')
                ? TalentInterestResource::collection($talent->interests)
                : [],
            'universal_blocks' => ProfileBlockResource::collection($this->visibleBlocks($talent, null)),
            'skills' => $skills->map(fn (TalentType $s) => [
                'id' => $s->id,
                'slug' => $s->slug,
                'name' => $s->getTranslations('name'),
                'specialities' => SpecialityResource::collection($specialities->get($s->id, collect())),
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
