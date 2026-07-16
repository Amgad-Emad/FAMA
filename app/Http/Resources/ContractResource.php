<?php

namespace App\Http\Resources;

use App\Models\Contract;
use Illuminate\Http\Request;

/**
 * @mixin Contract
 */
class ContractResource extends BaseResource
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
            'brand' => $this->whenLoaded('brand', fn () => $this->brand ? [
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
            ] : null),
            // The talent counterparty (shown in the brand contract room + inbox).
            'talent' => $this->whenLoaded('talent', fn () => $this->talent ? [
                'display_name' => $this->talent->display_name,
                'slug' => $this->talent->slug,
                'avatar_url' => $this->talent->avatar_url,
            ] : null),
            // The project this contract runs under, if any (ADR-F).
            'project' => $this->whenLoaded('project', fn () => $this->project ? [
                'title' => $this->project->title,
                'slug' => $this->project->slug,
            ] : null),
            // Unread free-messages for the current viewer (set by the inbox withCount,
            // scoped to the viewer's role — 0 when not counted).
            'unread_count' => (int) ($this->unread_count ?? 0),
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
