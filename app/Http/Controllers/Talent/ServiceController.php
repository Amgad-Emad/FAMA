<?php

namespace App\Http\Controllers\Talent;

use App\Http\Requests\Talent\ServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Services\TalentProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Services / rate-card manager (talent-spec). Paginated list; create/update;
 * pause/activate via the Service state machine; remove.
 */
class ServiceController extends TalentController
{
    public function __construct(private readonly TalentProfileService $profile) {}

    public function index(): View
    {
        return view('talent.services');
    }

    public function data(): JsonResponse
    {
        $paginator = $this->talent()->services()->orderBy('position')->paginate(15);

        return response()->paginated($paginator, ServiceResource::collection($paginator->getCollection()));
    }

    public function store(ServiceRequest $request): JsonResponse
    {
        $service = $this->profile->addService($this->talent(), $request->validated());

        return response()->success(new ServiceResource($service), __('Service added.'), status: 201);
    }

    public function update(ServiceRequest $request, Service $service): JsonResponse
    {
        $this->ensureOwns($service);
        $service = $this->profile->updateService($service, $request->validated());

        return response()->success(new ServiceResource($service), __('Service updated.'));
    }

    public function toggle(Service $service): JsonResponse
    {
        $this->ensureOwns($service);

        $service = $service->status->getValue() === 'active'
            ? $this->profile->pauseService($service)
            : $this->profile->activateService($service);

        return response()->success(new ServiceResource($service));
    }

    public function destroy(Service $service): JsonResponse
    {
        $this->ensureOwns($service);
        $this->profile->removeService($service);

        return response()->success(null, __('Service removed.'));
    }
}
