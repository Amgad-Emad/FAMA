<?php

namespace App\Http\Resources;

use App\Models\Deal;
use Illuminate\Http\Request;

/**
 * @mixin Deal
 */
class DealResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'title' => $this->title,
            'status' => (string) $this->status,
            'brief' => $this->brief,
            'agreed_amount' => $this->agreed_amount !== null ? (float) $this->agreed_amount : null,
            'currency' => $this->currency,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'initiated_by' => $this->initiated_by,
            'is_talent_turn' => (string) $this->status === 'awaiting_talent',
            'is_brand_turn' => (string) $this->status === 'awaiting_brand',
            'brand' => $this->whenLoaded('brand', fn () => ['name' => $this->brand?->name]),
            'current_step' => $this->whenLoaded('currentStep', fn () => $this->currentStep ? [
                'key' => $this->currentStep->key,
                'name' => $this->currentStep->name,
                'actor' => $this->currentStep->actor,
                'step_type' => $this->currentStep->step_type,
            ] : null),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
