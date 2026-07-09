<?php

namespace App\Http\Controllers\Api\V1\Talent;

use App\Http\Requests\Talent\ServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Services\TalentProfileService;
use Illuminate\Http\JsonResponse;

/**
 * @group Talent · Services
 *
 * @authenticated
 *
 * The talent's rate-card services — paginated list; create/update; pause/activate
 * via the Service state machine; remove. Delegates to TalentProfileService.
 */
class ServiceController extends TalentApiController
{
    public function __construct(private readonly TalentProfileService $profile) {}

    /**
     * List my services
     */
    public function index(): JsonResponse
    {
        $paginator = $this->talent()->services()->orderBy('position')->paginate(15);

        return response()->paginated($paginator, ServiceResource::collection($paginator->getCollection()));
    }

    /**
     * Add a service
     */
    public function store(ServiceRequest $request): JsonResponse
    {
        $service = $this->profile->addService($this->talent(), $request->validated());

        return response()->success(new ServiceResource($service), __('Service added.'), status: 201);
    }

    /**
     * Update a service
     */
    public function update(ServiceRequest $request, Service $service): JsonResponse
    {
        $this->ensureOwns($service);
        $service = $this->profile->updateService($service, $request->validated());

        return response()->success(new ServiceResource($service), __('Service updated.'));
    }

    /**
     * Pause / activate a service
     */
    public function toggle(Service $service): JsonResponse
    {
        $this->ensureOwns($service);

        $service = $service->status->getValue() === 'active'
            ? $this->profile->pauseService($service)
            : $this->profile->activateService($service);

        return response()->success(new ServiceResource($service));
    }

    /**
     * Remove a service
     */
    public function destroy(Service $service): JsonResponse
    {
        $this->ensureOwns($service);
        $this->profile->removeService($service);

        return response()->success(null, __('Service removed.'));
    }
}
