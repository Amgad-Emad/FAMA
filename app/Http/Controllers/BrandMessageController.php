<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\BrandProject;
use App\Models\Contract;
use App\Models\ContractFlow;
use App\Services\ContractService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Talent↔brand messaging entry — the mirror of Brand\TalentMessageController
 * (ADR-P). A talent messages a brand inside a contract (the public brand profile and
 * the campaign browser both point their "Message" CTA here), so this opens the
 * brand↔talent thread:
 *  - Guest / non-talent → talent authentication, returning here afterwards.
 *  - Authenticated talent → the most recent contract with this brand, or a fresh one
 *    started on the default flow (optionally tagged to the campaign the talent
 *    messaged about), then straight into the talent contract room.
 */
class BrandMessageController extends Controller
{
    public function __construct(private readonly ContractService $contracts) {}

    /**
     * Open (or lazily start) the talent↔brand contract for $brand and land in its room.
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

        // Reuse the latest existing contract with this brand, else start one so there is
        // always a thread to message in.
        $contract = Contract::where('brand_id', $brand->id)->where('talent_id', $talent->id)->latest()->first();

        if ($contract === null) {
            $flow = ContractFlow::where('is_default', true)->first() ?? ContractFlow::query()->firstOrFail();

            $contract = $this->contracts->initiate([
                'brand_id' => $brand->id,
                'talent_id' => $talent->id,
                'title' => __('Conversation with :name', ['name' => $brand->name]),
                'initiated_by' => 'talent',
            ], $flow);

            // Tag the contract to the campaign the talent messaged about, when it belongs
            // to this brand (brand_project_id is force-filled — it is not mass-assignable).
            $campaignId = $request->integer('project');
            if ($campaignId > 0) {
                $campaign = BrandProject::where('id', $campaignId)->where('brand_id', $brand->id)->first();
                if ($campaign !== null) {
                    $contract->forceFill(['brand_project_id' => $campaign->id])->save();
                }
            }
        }

        return redirect()->route('talent.contracts.show', $contract);
    }
}
