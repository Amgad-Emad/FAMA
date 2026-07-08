<?php

namespace App\Http\Controllers\Brand;

use App\Models\TalentType;
use App\Services\BrandOnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Brand onboarding wizard (brand-spec) — the 6-step payoff funnel. Each step
 * persists via Ajax through BrandOnboardingService; step 6 flips is_complete.
 */
class OnboardingController extends BrandController
{
    private const INDUSTRIES = ['fashion', 'beauty', 'food_beverage', 'lifestyle', 'tech', 'other'];

    private const STAGES = ['new', 'growing', 'established'];

    private const REACH = ['same_city', 'mena', 'international'];

    private const FREQUENCY = ['occasional', 'monthly', 'weekly', 'ongoing'];

    private const PROJECT_TYPES = ['editorial', 'lookbook', 'campaign_video', 'social_content', 'brand_identity'];

    private const MOODS = ['editorial', 'minimal', 'bold', 'warm', 'dark', 'playful', 'luxurious', 'raw', 'nostalgic', 'commercial'];

    private const BUDGETS = ['under_500', '500_2000', '2000_10000', '10000_plus'];

    public function __construct(private readonly BrandOnboardingService $onboarding) {}

    public function index(): View|RedirectResponse
    {
        if ($this->brand()->is_complete) {
            return redirect()->route('brand.dashboard');
        }

        $brand = $this->brand()->loadMissing('creativeNeed.talentTypes', 'creativeNeed.projectTypes', 'aesthetic.moodTags');

        return view('brand.onboarding', [
            'brand' => $brand,
            'talentTypes' => TalentType::orderBy('id')->get(),
            'projectTypes' => self::PROJECT_TYPES,
            'moods' => self::MOODS,
        ]);
    }

    public function identity(Request $request): JsonResponse
    {
        $this->onboarding->identity($this->brand(), $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'description.en' => ['nullable', 'string', 'max:255'],
            'description.ar' => ['nullable', 'string', 'max:255'],
            'industry' => ['required', Rule::in(self::INDUSTRIES)],
            'brand_stage' => ['required', Rule::in(self::STAGES)],
        ]));

        return response()->success(null, __('Saved.'));
    }

    public function location(Request $request): JsonResponse
    {
        $this->onboarding->location($this->brand(), $request->validate([
            'base_city' => ['required', 'string', 'max:255'],
            'base_country' => ['required', 'string', 'max:255'],
            'geographic_reach' => ['required', Rule::in(self::REACH)],
        ]));

        return response()->success(null, __('Saved.'));
    }

    public function creativeNeeds(Request $request): JsonResponse
    {
        $this->onboarding->creativeNeeds($this->brand(), $request->validate([
            'talent_type_ids' => ['array'],
            'talent_type_ids.*' => ['integer', 'exists:talent_types,id'],
            'project_types' => ['array'],
            'project_types.*' => [Rule::in(self::PROJECT_TYPES)],
            'project_frequency' => ['nullable', Rule::in(self::FREQUENCY)],
        ]));

        return response()->success(null, __('Saved.'));
    }

    public function aesthetic(Request $request): JsonResponse
    {
        $this->onboarding->aesthetic($this->brand(), $request->validate([
            'mood_tags' => ['array'],
            'mood_tags.*' => [Rule::in(self::MOODS)],
            'brand_references' => ['nullable', 'string', 'max:2000'],
        ]));

        return response()->success(null, __('Saved.'));
    }

    public function budget(Request $request): JsonResponse
    {
        $data = $request->validate(['budget_tier' => ['required', Rule::in(self::BUDGETS)]]);
        $this->onboarding->budget($this->brand(), $data['budget_tier']);

        return response()->success(null, __('Saved.'));
    }

    public function complete(): JsonResponse
    {
        $brand = $this->onboarding->complete($this->brand());

        return response()->success(
            ['is_complete' => (bool) $brand->is_complete, 'redirect' => route('brand.dashboard')],
            __('Welcome to Fama!'),
            status: 201,
        );
    }
}
