<?php

namespace App\Http\Controllers\Brand;

use App\Http\Controllers\Controller;
use App\Models\DealFlow;
use App\Models\Talent;
use App\Services\DealService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Brand↔talent messaging entry — the public profile's / discovery's "Message" CTA
 * points here (talent-spec public profile; ADR-P). A brand messages a talent inside
 * a deal, so this opens the brand↔talent thread:
 *  - Guest / non-brand → brand authentication, returning here afterwards.
 *  - Authenticated brand → the most recent deal with this talent, or a fresh one
 *    started on the default flow, then straight into the deal room (where the free
 *    message composer + the step actions live).
 */
class TalentMessageController extends Controller
{
    public function __construct(private readonly DealService $deals) {}

    /**
     * Open (or lazily start) the brand↔talent deal for $talent and land in its room.
     */
    public function __invoke(Request $request, Talent $talent): RedirectResponse
    {
        if (! Auth::guard('brand')->check()) {
            // Not a brand yet → authenticate, then come back here to open the thread.
            $request->session()->put('url.intended', route('brand.talents.message', ['talent' => $talent->slug]));

            return redirect()->route('login', ['role' => 'brand']);
        }

        $brand = Auth::guard('brand')->user();

        // Reuse the latest existing deal with this talent, else start one so there is
        // always a thread to message in.
        $deal = $brand->deals()->where('talent_id', $talent->id)->latest()->first();

        if ($deal === null) {
            $flow = DealFlow::where('is_default', true)->first() ?? DealFlow::query()->firstOrFail();

            $deal = $this->deals->initiate([
                'brand_id' => $brand->id,
                'talent_id' => $talent->id,
                'title' => __('Conversation with :name', ['name' => $talent->display_name]),
                'initiated_by' => 'brand',
            ], $flow);
        }

        return redirect()->route('brand.deals.show', $deal);
    }
}
