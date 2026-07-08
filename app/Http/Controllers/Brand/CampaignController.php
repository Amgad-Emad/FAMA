<?php

namespace App\Http\Controllers\Brand;

use App\Http\Requests\Brand\StoreCampaignRequest;
use App\Http\Requests\Brand\UpdateCampaignRequest;
use App\Http\Resources\CampaignResource;
use App\Http\Resources\DealResource;
use App\Models\Campaign;
use App\Models\TalentType;
use App\Services\CampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Campaigns manager + single-campaign workspace (brand-spec). Create/edit,
 * attach roles + media, drive status transitions, and view the deals running
 * under the campaign (deals.campaign_id). Everything delegates to CampaignService.
 */
class CampaignController extends BrandController
{
    public function __construct(private readonly CampaignService $campaigns) {}

    public function index(): View
    {
        return view('brand.campaigns.index', ['talentTypes' => TalentType::orderBy('id')->get()]);
    }

    public function data(): JsonResponse
    {
        $paginator = $this->brand()->campaigns()->with('media')->withCount('deals')->latest()->paginate(12);

        return response()->paginated($paginator, CampaignResource::collection($paginator->getCollection()));
    }

    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $campaign = $this->campaigns->create($this->brand(), $this->withRoleMap($request->validated()));

        return response()->success(
            ['id' => $campaign->id, 'slug' => $campaign->slug],
            __('Campaign created.'),
            status: 201,
        );
    }

    public function show(Campaign $campaign): View
    {
        $this->ensureOwns($campaign);

        return view('brand.campaigns.show', [
            'campaign' => $campaign,
            'talentTypes' => TalentType::orderBy('id')->get(),
        ]);
    }

    public function showData(Campaign $campaign): JsonResponse
    {
        $this->ensureOwns($campaign);
        $campaign->load(['media', 'talentTypes', 'gallery.media'])->loadCount('deals');
        $deals = $campaign->deals()->with(['talent', 'currentStep'])->latest()->get();

        return response()->success([
            'campaign' => new CampaignResource($campaign),
            'deals' => DealResource::collection($deals),
        ]);
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign): JsonResponse
    {
        $this->ensureOwns($campaign);
        $this->campaigns->update($campaign, $this->withRoleMap($request->validated()));

        return response()->success(null, __('Campaign updated.'));
    }

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

    public function setPublic(Request $request, Campaign $campaign): JsonResponse
    {
        $this->ensureOwns($campaign);
        $this->campaigns->setPublic($campaign, $request->boolean('public'));

        return response()->success(['is_public' => (bool) $campaign->fresh()->is_public], __('Visibility updated.'));
    }

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
