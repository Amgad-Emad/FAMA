<?php

namespace App\Http\Controllers\Api\V1\Brand;

use App\Services\BrandOnboardingService;
use App\Support\Brand\BrandOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @group Brand · Onboarding
 *
 * @authenticated
 *
 * The 6-step onboarding funnel. Each step persists through BrandOnboardingService
 * (transactional); the final step flips `is_complete`, which unlocks the discovery
 * feed. `status` reads the current step so a resumed app can pick up where it left.
 */
class OnboardingController extends BrandApiController
{
    public function __construct(private readonly BrandOnboardingService $onboarding) {}

    /**
     * Onboarding status
     *
     * Whether onboarding is complete plus the current saved values, so the app
     * can resume the wizard.
     */
    public function status(): JsonResponse
    {
        $brand = $this->brand()->load(['creativeNeed.talentTypes', 'creativeNeed.projectTypes', 'aesthetic.moodTags']);

        return response()->success([
            'is_complete' => (bool) $brand->is_complete,
            'name' => $brand->name,
            'industry' => $brand->industry,
            'brand_stage' => $brand->brand_stage,
            'base_city' => $brand->base_city,
            'base_country' => $brand->base_country,
            'geographic_reach' => $brand->geographic_reach,
            'talent_type_ids' => $brand->creativeNeed?->talentTypes->pluck('id')->values() ?? [],
            'project_types' => $brand->creativeNeed?->projectTypes->pluck('project_type')->values() ?? [],
            'project_frequency' => $brand->creativeNeed?->project_frequency,
            'budget_tier' => $brand->creativeNeed?->budget_tier,
            'mood_tags' => $brand->aesthetic?->moodTags->pluck('tag')->values() ?? [],
        ]);
    }

    /**
     * Step 1 · Identity
     */
    public function identity(Request $request): JsonResponse
    {
        $this->onboarding->identity($this->brand(), $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'description.en' => ['nullable', 'string', 'max:255'],
            'description.ar' => ['nullable', 'string', 'max:255'],
            'industry' => ['required', Rule::in(BrandOptions::INDUSTRIES)],
            'brand_stage' => ['required', Rule::in(BrandOptions::STAGES)],
        ]));

        return response()->success(null, __('Saved.'));
    }

    /**
     * Step 2 · Location
     */
    public function location(Request $request): JsonResponse
    {
        $this->onboarding->location($this->brand(), $request->validate([
            'base_city' => ['required', 'string', 'max:255'],
            'base_country' => ['required', 'string', 'max:255'],
            'geographic_reach' => ['required', Rule::in(BrandOptions::REACH)],
        ]));

        return response()->success(null, __('Saved.'));
    }

    /**
     * Step 3 · Creative needs
     */
    public function creativeNeeds(Request $request): JsonResponse
    {
        $this->onboarding->creativeNeeds($this->brand(), $request->validate([
            'talent_type_ids' => ['array'],
            'talent_type_ids.*' => ['integer', 'exists:talent_types,id'],
            'project_types' => ['array'],
            'project_types.*' => [Rule::in(BrandOptions::PROJECT_TYPES)],
            'project_frequency' => ['nullable', Rule::in(BrandOptions::FREQUENCY)],
        ]));

        return response()->success(null, __('Saved.'));
    }

    /**
     * Step 4 · Aesthetic
     */
    public function aesthetic(Request $request): JsonResponse
    {
        $this->onboarding->aesthetic($this->brand(), $request->validate([
            'mood_tags' => ['array'],
            'mood_tags.*' => [Rule::in(BrandOptions::MOODS)],
            'brand_references' => ['nullable', 'string', 'max:2000'],
        ]));

        return response()->success(null, __('Saved.'));
    }

    /**
     * Step 5 · Budget
     */
    public function budget(Request $request): JsonResponse
    {
        $data = $request->validate(['budget_tier' => ['required', Rule::in(BrandOptions::BUDGETS)]]);
        $this->onboarding->budget($this->brand(), $data['budget_tier']);

        return response()->success(null, __('Saved.'));
    }

    /**
     * Step 6 · Complete
     *
     * Flips `is_complete` and unlocks the dashboard/feed.
     */
    public function complete(): JsonResponse
    {
        $brand = $this->onboarding->complete($this->brand());

        return response()->success(['is_complete' => (bool) $brand->is_complete], __('Welcome to Fama!'), status: 201);
    }
}
