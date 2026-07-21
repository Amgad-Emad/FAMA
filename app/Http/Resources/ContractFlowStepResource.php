<?php

namespace App\Http\Resources;

use App\Models\ContractFlowStep;
use Illuminate\Http\Request;

/**
 * @mixin ContractFlowStep
 */
class ContractFlowStepResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'name' => $this->name,
            'instructions' => $this->instructions,
            'actor' => $this->actor,
            'step_type' => $this->step_type,
            'position' => (int) $this->position,
            'is_required' => (bool) $this->is_required,
            'is_skippable' => (bool) $this->is_skippable,
            'settings' => $this->settings ?? [],
        ];
    }
}
