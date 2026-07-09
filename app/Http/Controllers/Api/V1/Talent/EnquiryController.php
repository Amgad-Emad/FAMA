<?php

namespace App\Http\Controllers\Api\V1\Talent;

use App\Http\Resources\Api\V1\DealEnquiryResource;
use App\Models\DealEnquiry;
use Illuminate\Http\JsonResponse;

/**
 * @group Talent · Enquiries
 *
 * @authenticated
 *
 * Incoming pre-auth booking enquiries the talent received (the public booking CTA
 * writes these; they convert to deals once a brand picks them up). Read-only for
 * the talent.
 */
class EnquiryController extends TalentApiController
{
    /**
     * List my enquiries
     *
     * Paginated, newest first.
     */
    public function index(): JsonResponse
    {
        $paginator = $this->talent()->dealEnquiries()
            ->with('service')
            ->latest()
            ->paginate(15);

        return response()->paginated($paginator, DealEnquiryResource::collection($paginator->getCollection()));
    }

    /**
     * Get an enquiry
     */
    public function show(DealEnquiry $enquiry): JsonResponse
    {
        $this->ensureOwns($enquiry);

        return response()->success(new DealEnquiryResource($enquiry->load('service')));
    }
}
