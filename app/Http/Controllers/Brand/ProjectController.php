<?php

namespace App\Http\Controllers\Brand;

use App\Http\Requests\Brand\StoreBrandProjectRequest;
use App\Http\Requests\Brand\UpdateBrandProjectRequest;
use App\Http\Resources\BrandProjectResource;
use App\Http\Resources\ContractResource;
use App\Models\BrandProject;
use App\Models\BrandProjectMedia;
use App\Models\TalentType;
use App\Services\BrandProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Projects manager + single-campaign workspace (brand-spec). Create/edit,
 * attach roles + media, drive status transitions, and view the contracts running
 * under the project (contracts.brand_project_id). Everything delegates to BrandProjectService.
 */
class ProjectController extends BrandController
{
    public function __construct(private readonly BrandProjectService $projects) {}

    public function index(): View
    {
        return view('brand.projects.index', ['talentTypes' => TalentType::orderBy('id')->get()]);
    }

    public function data(): JsonResponse
    {
        $paginator = $this->brand()->projects()->with(['media', 'talentType'])->withCount('contracts')->latest()->paginate(12);

        return response()->paginated($paginator, BrandProjectResource::collection($paginator->getCollection()));
    }

    public function store(StoreBrandProjectRequest $request): JsonResponse
    {
        $campaign = $this->projects->create($this->brand(), $request->validated());

        return response()->success(
            ['id' => $campaign->id, 'slug' => $campaign->slug],
            __('Project created.'),
            status: 201,
        );
    }

    public function show(BrandProject $campaign): View
    {
        $this->ensureOwns($campaign);

        return view('brand.projects.show', [
            'campaign' => $campaign,
            'talentTypes' => TalentType::orderBy('id')->get(),
        ]);
    }

    public function showData(BrandProject $campaign): JsonResponse
    {
        $this->ensureOwns($campaign);
        $campaign->load(['media', 'talentType', 'gallery.media'])->loadCount('contracts');
        $contracts = $campaign->contracts()->with(['talent', 'currentStep'])->latest()->get();

        return response()->success([
            'campaign' => new BrandProjectResource($campaign),
            'contracts' => ContractResource::collection($contracts),
        ]);
    }

    public function update(UpdateBrandProjectRequest $request, BrandProject $campaign): JsonResponse
    {
        $this->ensureOwns($campaign);
        $this->projects->update($campaign, $request->validated());

        return response()->success(null, __('Project updated.'));
    }

    public function status(Request $request, BrandProject $campaign): JsonResponse
    {
        $this->ensureOwns($campaign);
        $action = $request->validate(['action' => ['required', Rule::in(['open', 'start', 'complete', 'cancel'])]])['action'];

        match ($action) {
            'open' => $this->projects->open($campaign),
            'start' => $this->projects->start($campaign),
            'complete' => $this->projects->complete($campaign),
            'cancel' => $this->projects->cancel($campaign),
        };

        return response()->success(['status' => $campaign->fresh()->status->getValue()], __('Project updated.'));
    }

    public function setPublic(Request $request, BrandProject $campaign): JsonResponse
    {
        $this->ensureOwns($campaign);
        $this->projects->setPublic($campaign, $request->boolean('public'));

        return response()->success(['is_public' => (bool) $campaign->fresh()->is_public], __('Visibility updated.'));
    }

    public function addMedia(Request $request, BrandProject $campaign): JsonResponse
    {
        $this->ensureOwns($campaign);
        $request->validate(['file' => ['required', 'image', 'max:5120']]);
        $item = $this->projects->addMedia($campaign, $request->file('file'));

        return response()->success(
            ['id' => $item->id, 'media_url' => $item->fresh()->media_url],
            __('Media added.'),
            status: 201,
        );
    }

    public function removeMedia(BrandProject $campaign, BrandProjectMedia $media): JsonResponse
    {
        $this->ensureOwns($campaign);
        abort_unless($media->brand_project_id === $campaign->id, 404);
        $media->delete();

        return response()->success(null, __('Media removed.'));
    }

    public function destroy(BrandProject $campaign): JsonResponse
    {
        $this->ensureOwns($campaign);
        $campaign->delete();

        return response()->success(null, __('Project deleted.'));
    }
}
