<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Campaign;
use Illuminate\View\View;

/**
 * Public brand pages (brand-spec, talent-facing) — `fama.com/brands/{slug}` and
 * its campaign detail. Thin + presentational: only a published brand is visible,
 * and only approved reviews / public campaigns are eager-loaded (no N+1).
 */
class BrandProfileController extends Controller
{
    /**
     * Show a published brand's public profile by slug (404 otherwise).
     */
    public function show(Brand $brand): View
    {
        abort_unless((bool) $brand->is_published, 404);

        $brand->load([
            'media', // brand logo + cover
            'credibility',
            'aesthetic.moodTags',
            'images.media',
            'socialHandles',
            'brandReviews' => fn ($query) => $query->where('is_approved', true)->with('talent')->latest(),
            'campaigns' => fn ($query) => $query->where('is_public', true)->where('status', '!=', 'cancelled')->with('media')->latest(),
        ]);

        return view('brand.public-profile', ['brand' => $brand]);
    }

    /**
     * Show a public campaign under a published brand (404 otherwise). The nested
     * binding is scoped so the campaign must belong to the brand.
     */
    public function campaign(Brand $brand, Campaign $campaign): View
    {
        abort_unless((bool) $brand->is_published && (bool) $campaign->is_public, 404);

        $campaign->load(['media', 'talentTypes', 'gallery.media']);

        return view('brand.public-campaign', ['brand' => $brand, 'campaign' => $campaign]);
    }
}
