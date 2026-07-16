<?php

namespace App\Http\Controllers\Brand;

use App\Services\BrandOnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * Creative-needs / preferences editor (brand-spec) — tune the talent types,
 * project types, frequency, and budget tier that drive the discovery feed.
 * Reuses the onboarding service's idempotent, transactional writes. The editor
 * UI was folded into the Profile editor (like Account), so the old page now
 * redirects there; the update endpoint stays (the Profile editor calls it).
 */
class CreativeNeedsController extends BrandController
{
    private const FREQUENCY = ['occasional', 'monthly', 'weekly', 'ongoing'];

    private const PROJECT_TYPES = ['editorial', 'lookbook', 'campaign_video', 'social_content', 'brand_identity'];

    private const BUDGETS = ['under_500', '500_2000', '2000_10000', '10000_plus'];

    public function __construct(private readonly BrandOnboardingService $onboarding) {}

    public function edit(): RedirectResponse
    {
        return redirect()->route('brand.profile');
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'talent_type_ids' => ['array'],
            'talent_type_ids.*' => ['integer', 'exists:talent_types,id'],
            'project_types' => ['array'],
            'project_types.*' => [Rule::in(self::PROJECT_TYPES)],
            'project_frequency' => ['nullable', Rule::in(self::FREQUENCY)],
            'budget_tier' => ['nullable', Rule::in(self::BUDGETS)],
        ]);

        $this->onboarding->creativeNeeds($this->brand(), Arr::only($data, ['talent_type_ids', 'project_types', 'project_frequency']));

        if (! empty($data['budget_tier'])) {
            $this->onboarding->budget($this->brand(), $data['budget_tier']);
        }

        return response()->success(null, __('Creative needs updated.'));
    }
}
