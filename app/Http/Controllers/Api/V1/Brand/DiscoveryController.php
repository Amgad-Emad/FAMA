<?php

namespace App\Http\Controllers\Api\V1\Brand;

use App\Http\Resources\TalentCardResource;
use App\Models\Talent;
use App\Queries\BrandTalentFeed;
use App\Services\BrandSignalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Brand · Discovery
 *
 * @authenticated
 *
 * The personalised talent feed (seeded from the brand's creative needs +
 * geographic reach, on `App\Queries\BrandTalentFeed`) plus the save/brief signal
 * writes that enrich the preference profile. Same query and signals as the web
 * feed; browsing records a `view` signal.
 */
class DiscoveryController extends BrandApiController
{
    public function __construct(
        private readonly BrandTalentFeed $feed,
        private readonly BrandSignalService $signals,
    ) {}

    /**
     * Discovery feed
     *
     * Paginated, personalised talents. Ad-hoc narrowing via `filter[...]`
     * (availability, city, type) and `sort` (view_count, created_at).
     *
     * @queryParam filter[availability] string Example: available
     * @queryParam filter[city] string Partial city match. Example: Cairo
     * @queryParam filter[type] string Comma-separated profession slugs. Example: model
     * @queryParam sort string view_count or created_at (prefix - for desc). Example: -view_count
     */
    public function feed(): JsonResponse
    {
        $paginator = $this->feed->paginate($this->brand());

        return response()->paginated($paginator, TalentCardResource::collection($paginator->getCollection()));
    }

    /**
     * Save a talent
     *
     * @bodyParam talent_id integer required The talent to save. Example: 1
     */
    public function save(Request $request): JsonResponse
    {
        $this->signals->save($this->brand(), $this->talentFrom($request));

        return response()->success(null, __('Saved.'));
    }

    /**
     * Send a brief to a talent
     *
     * @bodyParam talent_id integer required The talent to brief. Example: 1
     */
    public function brief(Request $request): JsonResponse
    {
        $this->signals->brief($this->brand(), $this->talentFrom($request));

        return response()->success(null, __('Brief sent.'));
    }

    /**
     * Resolve and validate the target talent from the request.
     */
    private function talentFrom(Request $request): Talent
    {
        $data = $request->validate(['talent_id' => ['required', 'integer', 'exists:talents,id']]);

        return Talent::findOrFail($data['talent_id']);
    }
}
