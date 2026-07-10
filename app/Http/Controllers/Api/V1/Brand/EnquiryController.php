<?php

namespace App\Http\Controllers\Api\V1\Brand;

use App\Http\Resources\Api\V1\DealEnquiryResource;
use App\Http\Resources\DealResource;
use App\Models\DealEnquiry;
use App\Services\DealService;
use Illuminate\Http\JsonResponse;

/**
 * @group Brand · Enquiries
 *
 * @authenticated
 *
 * Path B of deal initiation — the brand's PENDING pre-auth enquiries (public
 * Contact submissions whose `contact_email` matches the brand's email) and the
 * convert-to-deal action. Matching is by email ownership (403 otherwise); an
 * already-handled enquiry is 422. Delegates to DealService::convertEnquiry.
 */
class EnquiryController extends BrandApiController
{
    public function __construct(private readonly DealService $deals) {}

    /**
     * List my pending enquiries
     *
     * Paginated pre-auth enquiries addressed to the brand's email that are still
     * awaiting conversion (`status = new`).
     */
    public function index(): JsonResponse
    {
        $paginator = DealEnquiry::query()
            ->where('contact_email', $this->brand()->email)
            ->where('status', 'new')
            ->with(['service', 'talent'])
            ->latest()
            ->paginate(15);

        return response()->paginated($paginator, DealEnquiryResource::collection($paginator->getCollection()));
    }

    /**
     * Convert an enquiry to a deal
     *
     * Turns a matched pending enquiry into a real deal (carrying its talent /
     * service / brief), marks it converted, and notifies the talent. **403** if
     * the enquiry's email isn't the brand's; **422** if already handled.
     *
     * @response 201 scenario="Created" {"success":true,"data":{"id":1,"reference":"FAMA-2026-00001","status":"awaiting_brand"},"message":"Enquiry converted to a deal.","errors":null,"meta":{"room":"/api/v1/brand/deals/1"}}
     */
    public function convert(DealEnquiry $enquiry): JsonResponse
    {
        abort_unless($enquiry->contact_email === $this->brand()->email, 403);

        $deal = $this->deals->convertEnquiry($enquiry, $this->brand());
        $deal->load(['brand', 'talent', 'service', 'currentStep']);

        return response()->success(
            new DealResource($deal),
            __('Enquiry converted to a deal.'),
            ['room' => "/api/v1/brand/deals/{$deal->id}"],
            201,
        );
    }
}
