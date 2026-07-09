<?php

namespace App\Http\Resources;

use App\Models\DealFlow;
use Illuminate\Http\Request;

/**
 * @mixin DealFlow
 */
class DealFlowResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'applies_to' => $this->applies_to,
            'status' => $this->status->getValue(),
            'is_active' => (bool) $this->is_active,
            'is_default' => (bool) $this->is_default,
            'steps' => $this->whenLoaded('steps', fn () => DealFlowStepResource::collection($this->steps)),
            'steps_count' => $this->whenCounted('steps'),
            'deals_count' => $this->whenCounted('deals'),
        ];
    }
}
