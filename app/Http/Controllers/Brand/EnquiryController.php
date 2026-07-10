<?php

namespace App\Http\Controllers\Brand;

use App\Http\Resources\Api\V1\DealEnquiryResource;
use App\Models\DealEnquiry;
use App\Services\DealService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Brand pending enquiries (brand-spec, Path B of deal initiation) — the public
 * Contact submissions addressed to this brand's email that are still awaiting
 * conversion, plus the convert-to-deal action. Matching is by email ownership
 * (403 otherwise); conversion delegates to DealService::convertEnquiry.
 */
class EnquiryController extends BrandController
{
    public function __construct(private readonly DealService $deals) {}

    public function index(): View
    {
        return view('brand.enquiries');
    }

    public function data(): JsonResponse
    {
        $paginator = DealEnquiry::query()
            ->where('contact_email', $this->brand()->email)
            ->where('status', 'new')
            ->with(['service', 'talent'])
            ->latest()
            ->paginate(15);

        return response()->paginated($paginator, DealEnquiryResource::collection($paginator->getCollection()));
    }

    public function convert(DealEnquiry $enquiry): JsonResponse
    {
        abort_unless($enquiry->contact_email === $this->brand()->email, 403);

        $deal = $this->deals->convertEnquiry($enquiry, $this->brand());

        return response()->success(
            ['id' => $deal->id, 'redirect' => route('brand.deals.show', $deal)],
            __('Enquiry converted to a deal.'),
            status: 201,
        );
    }
}
