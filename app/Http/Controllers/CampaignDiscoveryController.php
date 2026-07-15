<?php

namespace App\Http\Controllers;

use App\Http\Resources\PublicCampaignCardResource;
use App\Models\Campaign;
use App\Models\TalentType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public campaign browsing (talent-facing) — the "opportunities" board: open,
 * public campaigns from published brands that a talent can browse and message
 * the brand about. Blade shell + Ajax feed, paginated + eager-loaded (no N+1).
 * Filters mirror the talent discovery page: a primary discipline (talent_type)
 * facet + advanced type/budget/location criteria.
 */
class CampaignDiscoveryController extends Controller
{
    public function index(): View
    {
        return view('public.campaigns', [
            // Disciplines catalog (talent_types) — the primary filter, grouped by scope.
            'types' => TalentType::orderBy('category')->orderBy('id')->get(),
        ]);
    }

    public function feed(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $types = array_values(array_filter(explode(',', (string) $request->query('type', ''))));
        $campaignType = $request->query('campaign_type');
        $city = trim((string) $request->query('city', ''));
        $budgetMin = $request->query('budget_min');
        $budgetMax = $request->query('budget_max');

        $paginator = Campaign::query()
            ->where('is_public', true)
            ->whereIn('status', ['open', 'in_progress'])
            ->whereHas('brand', fn ($query) => $query->where('is_published', true))
            ->when($q !== '', fn ($query) => $query->where('title', 'like', "%{$q}%"))
            ->when($types !== [], fn ($query) => $query->whereHas('talentTypes', fn ($tt) => $tt->whereIn('talent_types.slug', $types)))
            ->when(in_array($campaignType, ['campaign', 'shoot'], true), fn ($query) => $query->where('type', $campaignType))
            ->when($city !== '', fn ($query) => $query->where('location_city', 'like', "%{$city}%"))
            ->when(is_numeric($budgetMin), fn ($query) => $query->where(fn ($qq) => $qq->whereNull('budget_max')->orWhere('budget_max', '>=', (float) $budgetMin)))
            ->when(is_numeric($budgetMax), fn ($query) => $query->where(fn ($qq) => $qq->whereNull('budget_min')->orWhere('budget_min', '<=', (float) $budgetMax)))
            ->with(['brand.media', 'talentTypes', 'media'])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return response()->paginated($paginator, PublicCampaignCardResource::collection($paginator->getCollection()));
    }
}
