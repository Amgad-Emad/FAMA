<?php

namespace App\Queries;

use App\Models\Talent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Discovery / talent search (talent-spec) — a query object over published talents
 * built on spatie/laravel-query-builder. Filters (from `filter[...]` query params):
 * type & category (through the talent_talent_type pivot), availability, city,
 * country, equipment category, software name, and a free-text name match. Results
 * are eager-loaded (talentTypes + media) and paginated — no N+1.
 */
class TalentSearch
{
    public function paginate(int $perPage = 12): LengthAwarePaginator
    {
        $filters = [
            AllowedFilter::exact('availability', 'availability_status'),
            AllowedFilter::partial('city', 'base_city'),
            AllowedFilter::partial('country', 'base_country'),
            AllowedFilter::partial('q', 'display_name'),
            AllowedFilter::callback('type', fn ($query, $value) => $query->whereHas(
                'talentTypes', fn ($q) => $q->whereIn('talent_types.slug', (array) $value),
            )),
            AllowedFilter::callback('category', fn ($query, $value) => $query->whereHas(
                'talentTypes', fn ($q) => $q->whereIn('talent_types.category', (array) $value),
            )),
            AllowedFilter::callback('equipment', fn ($query, $value) => $query->whereHas(
                'equipment', fn ($q) => $q->whereIn('category', (array) $value),
            )),
            AllowedFilter::callback('software', fn ($query, $value) => $query->whereHas(
                'softwareStack', fn ($q) => $q->whereIn('software_name', (array) $value),
            )),
        ];

        return QueryBuilder::for(Talent::query()->where('is_published', true))
            ->allowedFilters(...$filters)
            ->allowedSorts('view_count', 'created_at')
            ->defaultSort('-view_count')
            ->with(['talentTypes', 'media'])
            ->paginate($perPage)
            ->withQueryString();
    }
}
