<?php

namespace App\Http\Resources;

use App\Models\Speciality;
use Illuminate\Http\Request;

/**
 * One entry of the controlled speciality vocabulary (ADR-Y).
 *
 * `is_regional` travels with the payload because it is presentational as well as
 * editorial: the spec renders the regional entries distinctly, and the admin
 * catalog needs to know which ones they are without a second lookup.
 *
 * @mixin Speciality
 */
class SpecialityResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->getTranslations('name'),
            'talent_type_id' => (int) $this->talent_type_id,
            'is_regional' => (bool) $this->is_regional,
            'is_active' => (bool) $this->is_active,
            'position' => (int) $this->position,
        ];
    }
}
