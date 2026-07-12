<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnquiryRequest;
use App\Models\Talent;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Deal initiation — the booking CTA (talent-spec). Public, no-login: a visitor's
 * enquiry (a brief only) lands in `deal_enquiries` and converts to a real deal
 * once they authenticate as a brand (ConvertEnquiryToDeal, brand-side Phase 2).
 * Enquiries are always allowed — there is no availability gate.
 */
class EnquiryController extends Controller
{
    public function create(string $slug): View
    {
        $talent = $this->publishedTalent($slug);

        return view('public.enquire', ['talent' => $talent]);
    }

    public function store(StoreEnquiryRequest $request, string $slug): JsonResponse
    {
        $talent = $this->publishedTalent($slug);

        $talent->dealEnquiries()->create($request->validated() + ['status' => 'new']);

        return response()->success(null, __('Your enquiry has been sent. The talent will be in touch.'), status: 201);
    }

    private function publishedTalent(string $slug): Talent
    {
        return Talent::query()->where('slug', $slug)->where('is_published', true)->firstOrFail();
    }
}
