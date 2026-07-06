<?php

namespace App\Http\Resources;

use App\Models\AgencyAffiliation;
use Illuminate\Http\Request;

/**
 * @mixin AgencyAffiliation
 */
class AffiliationResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agency_name' => $this->agency_name,
            'agency_url' => $this->agency_url,
            'representation_type' => $this->representation_type,
            'region' => $this->region,
            'is_current' => (bool) $this->is_current,
            'status' => (string) $this->status,
        ];
    }
}
