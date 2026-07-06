<?php

namespace App\Http\Resources;

use App\Models\Service;
use Illuminate\Http\Request;

/**
 * @mixin Service
 */
class ServiceResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->getTranslations('name'),
            'description' => $this->getTranslations('description'),
            'price' => $this->price !== null ? (float) $this->price : null,
            'currency' => $this->currency,
            'price_unit' => $this->price_unit,
            'duration_minutes' => $this->duration_minutes,
            'is_active' => (bool) $this->is_active,
            'status' => (string) $this->status,
            'position' => (int) $this->position,
        ];
    }
}
