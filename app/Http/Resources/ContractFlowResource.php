<?php

namespace App\Http\Resources;

use App\Models\ContractFlow;
use Illuminate\Http\Request;

/**
 * @mixin ContractFlow
 */
class ContractFlowResource extends BaseResource
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
            'steps' => $this->whenLoaded('steps', fn () => ContractFlowStepResource::collection($this->steps)),
            'steps_count' => $this->whenCounted('steps'),
            'contracts_count' => $this->whenCounted('contracts'),
        ];
    }
}
