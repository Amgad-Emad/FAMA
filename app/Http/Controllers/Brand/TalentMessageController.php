<?php

namespace App\Http\Controllers\Brand;

use App\Http\Controllers\Controller;
use App\Models\Talent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Reserved brand↔talent messaging entry — the public profile's primary "Message"
 * CTA points here (talent-spec public profile; ADR-P).
 *
 * Interim behaviour only (no chat wiring yet):
 *  - A visitor NOT authenticated as a brand is sent to brand authentication, with
 *    this talent's public profile stored as the intended return URL (so a later
 *    iteration can open the chat straight after auth).
 *  - An authenticated brand gets a brief "Messaging coming soon" flash back on the
 *    profile.
 */
class TalentMessageController extends Controller
{
    /**
     * Open (later) the brand↔talent chat for $talent; interim = auth redirect / stub.
     */
    public function __invoke(Request $request, Talent $talent): RedirectResponse
    {
        $profileUrl = route('talent.public', ['slug' => $talent->slug]);

        if (! Auth::guard('brand')->check()) {
            // Not a brand yet → authenticate as a brand, then return to the profile.
            $request->session()->put('url.intended', $profileUrl);

            return redirect()->route('login', ['role' => 'brand']);
        }

        // TODO(brand-messaging): the real brand↔talent chat attaches here — this is
        // ALSO the deal-initiation entry point. Open (or lazily create) the
        // deal_messages thread — or start a deal — for (brand = Auth::guard('brand')
        // ->user(), talent = $talent), then redirect into the deal room instead of
        // the stub below. Ties to the open deal-initiation slice (ADR-P).
        return redirect($profileUrl)->with('status', __('Messaging coming soon'));
    }
}
