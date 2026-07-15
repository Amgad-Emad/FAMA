<?php

namespace App\Http\Controllers;

use App\Http\Resources\BrandCardResource;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public brand discovery (talent-facing) — browse published brands hiring on
 * Fama. Mirrors the talent DiscoveryController: a Blade shell + an Ajax feed
 * backed by a paginated, eager-loaded query (no N+1). Only published brands are
 * ever exposed.
 */
class BrandDiscoveryController extends Controller
{
    /** Industry / stage / reach catalogs — the discovery filter facets. */
    private const INDUSTRIES = ['fashion', 'beauty', 'food_beverage', 'lifestyle', 'tech', 'other'];

    private const STAGES = ['new', 'growing', 'established'];

    private const REACH = ['same_city', 'mena', 'international'];

    public function index(): View
    {
        return view('public.brands', [
            'industries' => self::INDUSTRIES,
            'stages' => self::STAGES,
            'reaches' => self::REACH,
        ]);
    }

    public function feed(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $industry = $request->query('industry');
        $stage = $request->query('brand_stage');
        $reach = $request->query('geographic_reach');

        $paginator = Brand::query()
            ->where('is_published', true)
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->when(in_array($industry, self::INDUSTRIES, true), fn ($query) => $query->where('industry', $industry))
            ->when(in_array($stage, self::STAGES, true), fn ($query) => $query->where('brand_stage', $stage))
            ->when(in_array($reach, self::REACH, true), fn ($query) => $query->where('geographic_reach', $reach))
            ->when($request->boolean('verified'), fn ($query) => $query->where('is_verified', true))
            ->with('media') // logo_url resolves from the media library
            ->withCount(['projects' => fn ($query) => $query->where('is_public', true)->where('status', '!=', 'cancelled')])
            ->orderByDesc('is_verified')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return response()->paginated($paginator, BrandCardResource::collection($paginator->getCollection()));
    }
}
