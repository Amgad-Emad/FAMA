<?php

namespace App\Http\Resources;

use App\Models\TalentInterest;
use Illuminate\Http\Request;

/**
 * One "willing to explore" direction (ADR-Z). The structured skill/speciality
 * pair is what brands filter on, so both travel as ids AND as display names —
 * the editor needs the ids to re-select, the profile needs the names to render.
 *
 * @mixin TalentInterest
 */
class TalentInterestResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'talent_type_id' => (int) $this->talent_type_id,
            'speciality_id' => $this->speciality_id !== null ? (int) $this->speciality_id : null,
            'skill' => $this->whenLoaded('talentType', fn () => [
                'id' => (int) $this->talentType->id,
                'slug' => $this->talentType->slug,
                'name' => $this->talentType->getTranslation('name', $locale, false)
                    ?: $this->talentType->getTranslation('name', 'en', false),
                'icon' => $this->talentType->icon,
            ]),
            // Null is meaningful: the whole skill, not one kind of work.
            'speciality' => $this->whenLoaded('speciality', fn () => $this->speciality === null ? null : [
                'id' => (int) $this->speciality->id,
                'slug' => $this->speciality->slug,
                'name' => $this->speciality->getTranslation('name', $locale, false)
                    ?: $this->speciality->getTranslation('name', 'en', false),
            ]),
            'note' => $this->note,
            'position' => (int) $this->position,
        ];
    }
}
