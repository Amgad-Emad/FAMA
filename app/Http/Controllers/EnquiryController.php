<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnquiryRequest;
use App\Models\Talent;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Deal initiation — the booking CTA (talent-spec). Public, no-login: a visitor's
 * enquiry lands in `deal_enquiries` (checked against availability first) and
 * converts to a real deal once they authenticate as a brand (ConvertEnquiryToDeal,
 * brand-side Phase 2). Replaces the old fire-and-forget enquiry.
 */
class EnquiryController extends Controller
{
    public function create(string $slug): View
    {
        $talent = $this->publishedTalent($slug);
        $talent->load(['services' => fn ($query) => $query->where('is_active', true)]);

        return view('public.enquire', ['talent' => $talent]);
    }

    public function store(StoreEnquiryRequest $request, string $slug): JsonResponse
    {
        $talent = $this->publishedTalent($slug);

        if ($talent->availability_status->getValue() === 'unavailable') {
            throw new InvalidArgumentException(__('This talent is not currently taking bookings.'));
        }

        $talent->dealEnquiries()->create($request->validated() + ['status' => 'new']);

        return response()->success(null, __('Your enquiry has been sent. The talent will be in touch.'), status: 201);
    }

    private function publishedTalent(string $slug): Talent
    {
        return Talent::query()->where('slug', $slug)->where('is_published', true)->firstOrFail();
    }
}
