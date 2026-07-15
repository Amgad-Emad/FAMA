<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Campaign;
use App\Models\Deal;
use App\Models\DealFlow;
use App\Services\DealService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Talent↔brand messaging entry — the mirror of Brand\TalentMessageController
 * (ADR-P). A talent messages a brand inside a deal (the public brand profile and
 * the campaign browser both point their "Message" CTA here), so this opens the
 * brand↔talent thread:
 *  - Guest / non-talent → talent authentication, returning here afterwards.
 *  - Authenticated talent → the most recent deal with this brand, or a fresh one
 *    started on the default flow (optionally tagged to the campaign the talent
 *    messaged about), then straight into the talent deal room.
 */
class BrandMessageController extends Controller
{
    public function __construct(private readonly DealService $deals) {}

    /**
     * Open (or lazily start) the talent↔brand deal for $brand and land in its room.
     */
    public function __invoke(Request $request, Brand $brand): RedirectResponse
    {
        abort_unless((bool) $brand->is_published, 404);

        if (! Auth::guard('talent')->check()) {
            // Not a talent yet → authenticate, then come back here (query string kept,
            // so the campaign tag survives the round-trip).
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()->route('login', ['role' => 'talent']);
        }

        $talent = Auth::guard('talent')->user();

        // Reuse the latest existing deal with this brand, else start one so there is
        // always a thread to message in.
        $deal = Deal::where('brand_id', $brand->id)->where('talent_id', $talent->id)->latest()->first();

        if ($deal === null) {
            $flow = DealFlow::where('is_default', true)->first() ?? DealFlow::query()->firstOrFail();

            $deal = $this->deals->initiate([
                'brand_id' => $brand->id,
                'talent_id' => $talent->id,
                'title' => __('Conversation with :name', ['name' => $brand->name]),
                'initiated_by' => 'talent',
            ], $flow);

            // Tag the deal to the campaign the talent messaged about, when it belongs
            // to this brand (campaign_id is force-filled — it is not mass-assignable).
            $campaignId = $request->integer('campaign');
            if ($campaignId > 0) {
                $campaign = Campaign::where('id', $campaignId)->where('brand_id', $brand->id)->first();
                if ($campaign !== null) {
                    $deal->forceFill(['campaign_id' => $campaign->id])->save();
                }
            }
        }

        return redirect()->route('talent.deals.show', $deal);
    }
}
