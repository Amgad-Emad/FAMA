<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BlockTypeResource;
use App\Http\Resources\Api\V1\TalentTypeResource;
use App\Http\Resources\DealFlowResource;
use App\Models\BlockType;
use App\Models\DealFlow;
use App\Models\TalentType;
use App\Support\Brand\BrandOptions;
use Illuminate\Http\JsonResponse;

/**
 * @group Reference / lookups
 *
 * Public catalog data the mobile app needs to render dynamic UI (dropdowns,
 * profession pickers, the block catalog, the deal flows on offer). Read-only and
 * unauthenticated — onboarding forms need these before a token exists.
 * Translatable names come back in the request locale (Accept-Language).
 */
class LookupController extends Controller
{
    /**
     * Talent types
     *
     * The full profession catalog.
     *
     * @unauthenticated
     */
    public function talentTypes(): JsonResponse
    {
        return response()->success(
            TalentTypeResource::collection(TalentType::orderBy('id')->get())
        );
    }

    /**
     * Block types
     *
     * The active profile-block catalog (what a talent can add + how it renders).
     *
     * @unauthenticated
     */
    public function blockTypes(): JsonResponse
    {
        return response()->success(
            BlockTypeResource::collection(BlockType::where('is_active', true)->orderBy('position')->get())
        );
    }

    /**
     * Deal flows
     *
     * The active deal flows on offer, each with its ordered steps so the app can
     * preview the deal loop before starting one.
     *
     * @unauthenticated
     */
    public function dealFlows(): JsonResponse
    {
        return response()->success(
            DealFlowResource::collection(
                DealFlow::active()->with('steps')->orderByDesc('is_default')->orderBy('name')->get()
            )
        );
    }

    /**
     * Option lists
     *
     * The enum option lists (brand + talent) that back the app's select inputs, so
     * forms stay in sync with server-side validation.
     *
     * @unauthenticated
     */
    public function options(): JsonResponse
    {
        return response()->success([
            'brand' => [
                'industries' => BrandOptions::INDUSTRIES,
                'stages' => BrandOptions::STAGES,
                'geographic_reach' => BrandOptions::REACH,
                'project_frequency' => BrandOptions::FREQUENCY,
                'project_types' => BrandOptions::PROJECT_TYPES,
                'moods' => BrandOptions::MOODS,
                'budgets' => BrandOptions::BUDGETS,
                'social_platforms' => BrandOptions::PLATFORMS,
                'company_sizes' => BrandOptions::COMPANY_SIZES,
                'campaign_types' => ['campaign', 'shoot'],
            ],
            'talent' => [
                'availability' => ['available', 'booked', 'unavailable'],
                'rate_tiers' => ['emerging', 'established', 'premium', 'elite'],
                'booking_types' => ['email', 'calendar', 'form', 'external'],
                'representation_types' => ['exclusive', 'non_exclusive', 'mother_agency', 'freelance'],
                'service_price_units' => ['hour', 'day', 'project', 'fixed'],
            ],
        ]);
    }
}
