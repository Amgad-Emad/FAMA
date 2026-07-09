<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TalentResource;
use App\Http\Resources\TalentCardResource;
use App\Models\Talent;
use App\Queries\TalentSearch;
use Illuminate\Http\JsonResponse;

/**
 * @group Discovery
 *
 * Public, read-only talent discovery for the mobile app — the same
 * spatie/laravel-query-builder search the web feed uses, so filters and sorting
 * stay identical across web and API. Published talents only.
 */
class TalentController extends Controller
{
    public function __construct(private readonly TalentSearch $search) {}

    /**
     * List talents
     *
     * Paginated, filterable discovery feed. Supply `filter[...]` params
     * (type, category, availability, city, country, equipment, software, q) and
     * `sort` (view_count, created_at) exactly as the web feed does.
     *
     * @unauthenticated
     *
     * @queryParam filter[type] string Comma-separated profession slugs. Example: photographer
     * @queryParam filter[city] string Partial city match. Example: Cairo
     * @queryParam filter[availability] string One of available, booked, unavailable. Example: available
     * @queryParam filter[q] string Free-text display-name match. Example: amgad
     * @queryParam sort string view_count or created_at (prefix with - for descending). Example: -view_count
     * @queryParam page integer The page number. Example: 1
     */
    public function index(): JsonResponse
    {
        $paginator = $this->search->paginate();

        return response()->paginated($paginator, TalentCardResource::collection($paginator->getCollection()));
    }

    /**
     * Show a talent
     *
     * The full public passport for one published talent, resolved by slug, with
     * profession(s), rate-card services and approved reviews. Translatable
     * fields come back in the request locale (Accept-Language).
     *
     * @unauthenticated
     */
    public function show(Talent $talent): JsonResponse
    {
        abort_unless((bool) $talent->is_published, 404);

        $talent->load([
            'talentTypes',
            'services',
            'reviews' => fn ($query) => $query->where('is_approved', true),
        ]);

        return response()->success(new TalentResource($talent));
    }
}
