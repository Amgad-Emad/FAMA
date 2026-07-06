<?php

namespace App\Actions\Deals;

use App\Actions\Contracts\Action;
use App\Models\Brand;
use App\Models\Deal;
use App\Models\DealEnquiry;
use App\Models\DealFlow;
use InvalidArgumentException;

/**
 * Convert a pre-auth enquiry into a real deal once the visitor has become a
 * brand. Initiates the deal from the enquiry's brief/service, then marks the
 * enquiry converted and links `converted_deal_id`.
 */
class ConvertEnquiryToDeal implements Action
{
    public function __construct(private readonly InitiateDeal $initiate) {}

    public function __invoke(DealEnquiry $enquiry, Brand $brand, DealFlow $flow): Deal
    {
        if ($enquiry->status !== 'new') {
            throw new InvalidArgumentException('This enquiry has already been handled.');
        }

        $enquiry->loadMissing('talent');

        $deal = ($this->initiate)([
            'brand_id' => $brand->getKey(),
            'talent_id' => $enquiry->talent_id,
            'service_id' => $enquiry->service_id,
            'title' => 'Booking with '.($enquiry->talent?->display_name ?? 'talent'),
            'brief' => $enquiry->brief,
            'initiated_by' => 'brand',
        ], $flow);

        $enquiry->update(['status' => 'converted', 'converted_deal_id' => $deal->id]);

        return $deal;
    }
}
