<?php

namespace App\Actions\Contracting;

use App\Actions\Contracts\Action;
use App\Models\Brand;
use App\Models\Contract;
use App\Models\ContractEnquiry;
use App\Models\ContractFlow;
use InvalidArgumentException;

/**
 * Convert a pre-auth enquiry into a real contract once the visitor has become a
 * brand. Initiates the contract from the enquiry's brief, then marks the
 * enquiry converted and links `converted_contract_id`.
 */
class ConvertEnquiryToContract implements Action
{
    public function __construct(private readonly InitiateContract $initiate) {}

    public function __invoke(ContractEnquiry $enquiry, Brand $brand, ContractFlow $flow): Contract
    {
        if ($enquiry->status !== 'new') {
            throw new InvalidArgumentException('This enquiry has already been handled.');
        }

        $enquiry->loadMissing('talent');

        $contract = ($this->initiate)([
            'brand_id' => $brand->getKey(),
            'talent_id' => $enquiry->talent_id,
            'title' => 'Booking with '.($enquiry->talent?->display_name ?? 'talent'),
            'brief' => $enquiry->brief,
            'initiated_by' => 'brand',
        ], $flow);

        $enquiry->update(['status' => 'converted', 'converted_contract_id' => $contract->id]);

        return $contract;
    }
}
