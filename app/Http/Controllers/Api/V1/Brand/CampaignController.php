<?php

namespace App\Http\Controllers\Api\V1\Brand;

use App\Http\Requests\Brand\StoreCampaignRequest;
use App\Http\Requests\Brand\UpdateCampaignRequest;
use App\Http\Resources\CampaignResource;
use App\Http\Resources\DealResource;
use App\Models\Campaign;
use App\Services\CampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @group Brand · Campaigns
 *
 * @authenticated
 *
 * Campaigns manager + single-campaign workspace: create/edit, roles + media,
 * status transitions, and the deals running under a campaign (`deals.campaign_id`).
 * Everything delegates to CampaignService.
 */
class CampaignController extends BrandApiController
{
    public function __construct(private readonly CampaignService $campaigns) {}

    /**
     * List my campaigns
     *
     * Paginated, newest first, with `deals_count`.
     */
    public function index(): JsonResponse
    {
        $paginator = $this->brand()->campaigns()->with('media')->withCount('deals')->latest()->paginate(12);

        return response()->paginated($paginator, CampaignResource::collection($paginator->getCollection()));
    }

    /**
     * Create a campaign
     */
    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $campaign = $this->campaigns->create($this->brand(), $this->withRoleMap($request->validated()));

        return response()->success(new CampaignResource($campaign->load('talentTypes')), __('Campaign created.'), status: 201);
    }

    /**
     * Get a campaign
     *
     * The campaign (roles + gallery) and the deals running under it.
     */
    public function show(Campaign $campaign): JsonResponse
    {
        $this->ensureOwns($campaign);
        $campaign->load(['media', 'talentTypes', 'gallery.media'])->loadCount('deals');
        $deals = $campaign->deals()->with(['talent', 'currentStep'])->latest()->get();

        return response()->success([
            'campaign' => new CampaignResource($campaign),
            'deals' => DealResource::collection($deals),
        ]);
    }

    /**
     * Update a campaign
     */
    public function update(UpdateCampaignRequest $request, Campaign $campaign): JsonResponse
    {
        $this->ensureOwns($campaign);
        $this->campaigns->update($campaign, $this->withRoleMap($request->validated()));

        return response()->success(new CampaignResource($campaign->fresh()->load('talentTypes')), __('Campaign updated.'));
    }

    /**
     * Transition a campaign's status
     *
     * @bodyParam action string required One of open, start, complete, cancel. Example: open
     */
    public function status(Request $request, Campaign $campaign): JsonResponse
    {
        $this->ensureOwns($campaign);
        $action = $request->validate(['action' => ['required', Rule::in(['open', 'start', 'complete', 'cancel'])]])['action'];

        match ($action) {
            'open' => $this->campaigns->open($campaign),
            'start' => $this->campaigns->start($campaign),
            'complete' => $this->campaigns->complete($campaign),
            'cancel' => $this->campaigns->cancel($campaign),
        };

        return response()->success(['status' => $campaign->fresh()->status->getValue()], __('Campaign updated.'));
    }

    /**
     * Set a campaign public / private
     *
     * @bodyParam public boolean required Whether the campaign is publicly listed. Example: true
     */
    public function setPublic(Request $request, Campaign $campaign): JsonResponse
    {
        $this->ensureOwns($campaign);
        $this->campaigns->setPublic($campaign, $request->boolean('public'));

        return response()->success(['is_public' => (bool) $campaign->fresh()->is_public], __('Visibility updated.'));
    }

    /**
     * Add campaign media
     *
     * Multipart `file`. Returns the created media row + its URL.
     */
    public function addMedia(Request $request, Campaign $campaign): JsonResponse
    {
        $this->ensureOwns($campaign);
        $request->validate(['file' => ['required', 'image', 'max:5120']]);
        $item = $this->campaigns->addMedia($campaign, $request->file('file'));

        return response()->success(
            ['id' => $item->id, 'media_url' => $item->fresh()->media_url],
            __('Media added.'),
            status: 201,
        );
    }

    /**
     * Delete a campaign
     */
    public function destroy(Campaign $campaign): JsonResponse
    {
        $this->ensureOwns($campaign);
        $campaign->delete();

        return response()->success(null, __('Campaign deleted.'));
    }

    /**
     * Fold the validated roles list into the CampaignService [type_id => qty] map.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withRoleMap(array $data): array
    {
        if (array_key_exists('roles', $data)) {
            $data['roles'] = collect($data['roles'])
                ->mapWithKeys(fn ($role) => [$role['talent_type_id'] => $role['quantity']])
                ->all();
        }

        return $data;
    }
}
