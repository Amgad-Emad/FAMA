<?php

namespace App\Http\Controllers;

use App\Http\Resources\TalentCardResource;
use App\Models\TalentType;
use App\Queries\TalentSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Discovery / search page (talent-spec, public) — browse & filter published
 * talents. The page is a Blade shell; results come from an Ajax endpoint backed
 * by the TalentSearch query object (spatie/laravel-query-builder), paginated and
 * eager-loaded.
 */
class DiscoveryController extends Controller
{
    public function __construct(private readonly TalentSearch $search) {}

    public function index(): View
    {
        return view('public.discover', [
            // Skills catalog (talent_types) — the primary filter, grouped by category.
            'types' => TalentType::orderBy('category')->orderBy('id')->get(),
            'equipmentCategories' => ['camera', 'lens', 'lighting', 'audio', 'grip', 'drone', 'accessory'],
            'softwareOptions' => ['Figma', 'Photoshop', 'Illustrator', 'After Effects', 'Lightroom', 'Premiere Pro'],
            'lookOptions' => ['Editorial', 'Commercial', 'Runway', 'Fitness', 'Beauty'],
        ]);
    }

    public function search(): JsonResponse
    {
        $paginator = $this->search->paginate();

        return response()->paginated($paginator, TalentCardResource::collection($paginator->getCollection()));
    }
}
