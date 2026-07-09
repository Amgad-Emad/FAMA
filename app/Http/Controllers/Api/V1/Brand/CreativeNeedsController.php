<?php

namespace App\Http\Controllers\Api\V1\Brand;

use App\Services\BrandOnboardingService;
use App\Support\Brand\BrandOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * @group Brand · Creative needs
 *
 * @authenticated
 *
 * The talent types, project types, frequency and budget tier that drive the
 * discovery feed. Reuses the onboarding service's idempotent, transactional
 * writes.
 */
class CreativeNeedsController extends BrandApiController
{
    public function __construct(private readonly BrandOnboardingService $onboarding) {}

    /**
     * Get my creative needs
     */
    public function show(): JsonResponse
    {
        $need = $this->brand()->load(['creativeNeed.talentTypes', 'creativeNeed.projectTypes'])->creativeNeed;

        return response()->success([
            'talent_type_ids' => $need?->talentTypes->pluck('id')->values() ?? [],
            'project_types' => $need?->projectTypes->pluck('project_type')->values() ?? [],
            'project_frequency' => $need?->project_frequency,
            'budget_tier' => $need?->budget_tier,
        ]);
    }

    /**
     * Update my creative needs
     */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'talent_type_ids' => ['array'],
            'talent_type_ids.*' => ['integer', 'exists:talent_types,id'],
            'project_types' => ['array'],
            'project_types.*' => [Rule::in(BrandOptions::PROJECT_TYPES)],
            'project_frequency' => ['nullable', Rule::in(BrandOptions::FREQUENCY)],
            'budget_tier' => ['nullable', Rule::in(BrandOptions::BUDGETS)],
        ]);

        $this->onboarding->creativeNeeds($this->brand(), Arr::only($data, ['talent_type_ids', 'project_types', 'project_frequency']));

        if (! empty($data['budget_tier'])) {
            $this->onboarding->budget($this->brand(), $data['budget_tier']);
        }

        return response()->success(null, __('Creative needs updated.'));
    }
}
