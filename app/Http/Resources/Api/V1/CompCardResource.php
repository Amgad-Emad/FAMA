<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\BaseResource;
use App\Models\CompCard;
use Illuminate\Http\Request;

/**
 * @mixin CompCard
 *
 * The model comp-card (stats) contract — the 1:1 record behind a talent's
 * measurements block.
 */
class CompCardResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'height_cm' => $this->height_cm,
            'bust_cm' => $this->bust_cm,
            'waist_cm' => $this->waist_cm,
            'hips_cm' => $this->hips_cm,
            'shoe_size' => $this->shoe_size,
            'dress_size' => $this->dress_size,
            'hair_color' => $this->hair_color,
            'eye_color' => $this->eye_color,
            'skin_tone' => $this->skin_tone,
            'measurements' => $this->measurements,
        ];
    }
}
