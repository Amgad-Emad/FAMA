<?php

namespace App\Queries;

use App\Models\Brand;
use App\Models\Talent;
use App\Services\BrandSignalService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * The brand discovery feed / matching layer (brand-spec workflow 4). A
 * personalised talent feed seeded from the brand's creative needs (the promoted
 * talent-type pivot) and geographic_reach, on top of spatie/laravel-query-builder
 * for ad-hoc narrowing. Paginated + eager-loaded (no N+1). Browsing writes a
 * `view` signal to enrich the preference profile.
 *
 * Aesthetic weighting (mood tags) is not applied as a hard filter here — talents
 * carry no mood signal yet — so brand_aesthetics informs ordering/weighting in a
 * later pass, not selection. Documented in docs/architecture.md.
 */
class BrandTalentFeed
{
    public function __construct(private readonly BrandSignalService $signals) {}

    public function paginate(Brand $brand, int $perPage = 12, bool $recordBrowse = true): LengthAwarePaginator
    {
        $brand->loadMissing('creativeNeed.talentTypes');
        $neededTypeIds = $brand->creativeNeed?->talentTypes->pluck('id')->all() ?? [];

        $base = Talent::query()->where('is_published', true);

        // Creative needs → the professions the brand hires.
        if ($neededTypeIds !== []) {
            $base->whereHas('talentTypes', fn ($q) => $q->whereIn('talent_types.id', $neededTypeIds));
        }

        // geographic_reach → same_city narrows to the brand's city; mena/international stay open.
        if ($brand->geographic_reach === 'same_city' && $brand->base_city !== null) {
            $base->where('base_city', $brand->base_city);
        }

        $filters = [
            AllowedFilter::exact('availability', 'availability_status'),
            AllowedFilter::partial('city', 'base_city'),
            AllowedFilter::callback('type', fn ($q, $value) => $q->whereHas(
                'talentTypes', fn ($t) => $t->whereIn('talent_types.slug', (array) $value),
            )),
        ];

        $feed = QueryBuilder::for($base)
            ->allowedFilters(...$filters)
            ->allowedSorts('view_count', 'created_at')
            ->defaultSort('-view_count')
            ->with(['talentTypes', 'media'])
            ->paginate($perPage)
            ->withQueryString();

        if ($recordBrowse) {
            $this->signals->record($brand, 'view', null, ['feed' => true, 'results' => $feed->total()]);
        }

        return $feed;
    }
}
