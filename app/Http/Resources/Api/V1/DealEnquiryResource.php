<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\BaseResource;
use App\Models\DealEnquiry;
use Illuminate\Http\Request;

/**
 * @mixin DealEnquiry
 *
 * A pre-auth booking enquiry as seen by the talent who received it. Links to the
 * converted deal once a brand has authenticated and picked it up.
 */
class DealEnquiryResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contact_name' => $this->contact_name,
            'contact_email' => $this->contact_email,
            'contact_company' => $this->contact_company,
            'brief' => $this->brief,
            'status' => $this->status,
            'converted_deal_id' => $this->converted_deal_id,
            'service' => $this->whenLoaded('service', fn () => $this->service
                ? ['id' => $this->service->id, 'name' => $this->service->getTranslation('name', app()->getLocale())]
                : null),
            'talent' => $this->whenLoaded('talent', fn () => $this->talent
                ? ['id' => $this->talent->id, 'display_name' => $this->talent->display_name, 'slug' => $this->talent->slug]
                : null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
