<?php

namespace App\Queries;

use App\Models\Brand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Public brand directory / search — a query object over published brands, built
 * on spatie/laravel-query-builder so the whitelisted filters and sorts are the
 * documented contract. Talent-facing (browse the brands on the platform).
 *
 * Filters (from `filter[...]`): industry, brand_stage, geographic_reach (exact),
 * city / country (partial), verified (exact boolean), q (partial name). Sorts:
 * created_at, name (default newest). Eager-loads media (logo/cover accessors) so
 * the list is N+1-free; paginated.
 */
class BrandSearch
{
    public function paginate(int $perPage = 12): LengthAwarePaginator
    {
        $filters = [
            AllowedFilter::exact('industry'),
            AllowedFilter::exact('stage', 'brand_stage'),
            AllowedFilter::exact('reach', 'geographic_reach'),
            AllowedFilter::exact('verified', 'is_verified'),
            AllowedFilter::partial('city', 'base_city'),
            AllowedFilter::partial('country', 'base_country'),
            AllowedFilter::partial('q', 'name'),
        ];

        return QueryBuilder::for(Brand::query()->where('is_published', true))
            ->allowedFilters(...$filters)
            ->allowedSorts('created_at', 'name')
            ->defaultSort('-created_at')
            ->with('media')
            ->paginate($perPage)
            ->withQueryString();
    }
}
