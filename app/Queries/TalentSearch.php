<?php

namespace App\Queries;

use App\Models\Talent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Discovery / talent search (talent-spec) — a query object over published talents
 * built on spatie/laravel-query-builder. Filters (from `filter[...]` query params):
 * type & category (through the talent_talent_type pivot), city, country,
 * equipment category (crew scope), software name (creative scope), looks (model
 * scope — matched on the English `look_types.name` path, backed by a functional
 * index), speciality slug (the second level of the vocabulary — ADR-Y),
 * `available` / `will_travel` (the Now strip) and `open_to` (a stated direction
 * from Willing to explore — ADR-Z), and a free-text name match. (Availability filtering was removed — ADR-L.)
 * Results are eager-loaded (talentTypes + media) and paginated — no N+1.
 */
class TalentSearch
{
    public function paginate(int $perPage = 12): LengthAwarePaginator
    {
        $filters = [
            AllowedFilter::partial('city', 'base_city'),
            AllowedFilter::partial('country', 'base_country'),
            AllowedFilter::partial('q', 'display_name'),
            AllowedFilter::callback('type', fn ($query, $value) => $query->whereHas(
                'talentTypes', fn ($q) => $q->whereIn('talent_types.slug', (array) $value),
            )),
            AllowedFilter::callback('category', fn ($query, $value) => $query->whereHas(
                'talentTypes', fn ($q) => $q->whereIn('talent_types.category', (array) $value),
            )),
            // Speciality — the second level of the vocabulary (ADR-Y). Matched on
            // the SLUG, which is unique per skill and not globally: passing
            // `fashion` legitimately matches fashion photographers, fashion
            // models AND fashion stylists, and narrowing to one of them is what
            // combining it with filter[type] is for. Retired entries still match
            // so a talent never drops out of search because the catalog moved on.
            AllowedFilter::callback('speciality', fn ($query, $value) => $query->whereHas(
                'specialities', fn ($q) => $q->whereIn('specialities.slug', (array) $value),
            )),
            // NOW (ADR-Z). `available` is a plain boolean filter — availability is
            // INFORMATION, never a gate (ADR-L still stands): an unavailable
            // talent is still discoverable, still enquirable, and only drops out
            // when a brand explicitly asks to narrow to who is free.
            AllowedFilter::callback('available', fn ($query, $value) => $query
                ->where('is_available', $this->asBool($value))),
            AllowedFilter::callback('will_travel', fn ($query, $value) => $query
                ->where('will_travel', $this->asBool($value))),
            // WILLING TO EXPLORE (ADR-Z) — matched on the skill slug of a stated
            // direction. This is why the section is structured rather than free
            // text: a brand can find someone who WANTS the work but has not done
            // it yet, which no keyword search over prose would surface.
            AllowedFilter::callback('open_to', fn ($query, $value) => $query->whereHas(
                'interests.talentType', fn ($q) => $q->whereIn('talent_types.slug', (array) $value),
            )),
            AllowedFilter::callback('equipment', fn ($query, $value) => $query->whereHas(
                'equipment', fn ($q) => $q->whereIn('category', (array) $value),
            )),
            AllowedFilter::callback('software', fn ($query, $value) => $query->whereHas(
                'softwareStack', fn ($q) => $q->whereIn('software_name', (array) $value),
            )),
            // Model-scope "Looks" filter — matches the English name path (indexed).
            AllowedFilter::callback('looks', fn ($query, $value) => $query->whereHas(
                'lookTypes', fn ($q) => $q->whereIn('name->en', (array) $value),
            )),
        ];

        return QueryBuilder::for(Talent::query()->where('is_published', true))
            ->allowedFilters(...$filters)
            ->allowedSorts('view_count', 'created_at')
            ->defaultSort('-view_count')
            ->with(['talentTypes', 'specialities', 'media'])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Coerce a query-string filter value to a boolean. spatie hands a callback
     * filter either a scalar or an array (`filter[available]=1,0`), so take the
     * first entry and read the usual truthy spellings — "1", "true", "on", "yes".
     */
    private function asBool(mixed $value): bool
    {
        $first = is_array($value) ? ($value[0] ?? null) : $value;

        return filter_var($first, FILTER_VALIDATE_BOOLEAN);
    }
}
