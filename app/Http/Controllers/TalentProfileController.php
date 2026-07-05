<?php

namespace App\Http\Controllers;

use App\Events\TalentProfileViewed;
use App\Models\Talent;
use Illuminate\View\View;

/**
 * Public talent profile — `fama.com/{slug}` (schema-master §1, talent-spec).
 * Thin: eager-loads a published talent with everything the profile renders,
 * bumps the view counter, and returns the view. Only visible blocks and approved
 * reviews / active services are loaded, so the template stays presentational.
 */
class TalentProfileController extends Controller
{
    /**
     * Show a published talent's public profile by slug (404 otherwise).
     */
    public function show(string $slug): View
    {
        $talent = Talent::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->with([
                'media',
                'talentTypes',
                'profileBlocks' => fn ($query) => $query->where('is_visible', true),
                'portfolioItems.media',
                'compCard',
                'services' => fn ($query) => $query->where('is_active', true),
                'reviews' => fn ($query) => $query->approved(),
                'brandCollabs.media',
                'lookTypes',
                'digitals.media',
                'showreels.media',
                'equipment',
                'caseStudies.media',
                'softwareStack.media',
                'agencyAffiliations.media',
                'pressFeatures.media',
            ])
            ->firstOrFail();

        TalentProfileViewed::dispatch($talent);

        return view('talent.profile', ['talent' => $talent]);
    }
}
