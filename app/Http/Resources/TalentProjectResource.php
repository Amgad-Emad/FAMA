<?php

namespace App\Http\Resources;

use App\Models\Project;
use App\Models\Speciality;
use App\Models\TalentType;
use Illuminate\Http\Request;

/**
 * @mixin Project
 *
 * One of the talent's own portfolio projects, as the Projects editor sees it:
 * both locales of every translatable field (the editor writes them), the cover
 * derived from medialibrary, and the full skill tag set (ADR-X).
 *
 * Callers MUST eager-load `media` and `talentTypes` — `cover_image_url` goes
 * through getFirstMediaUrl(), which loadMissing()s internally and therefore
 * slips past preventLazyLoading.
 */
class TalentProjectResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'position' => (int) $this->position,
            'title' => $this->getTranslations('title'),
            'role' => $this->getTranslations('role'),
            'summary' => $this->getTranslations('summary'),
            'body' => $this->getTranslations('body'),
            // What the card shows, already resolved in the viewer's locale.
            'label' => $this->getTranslation('title', $locale, false) ?: $this->getTranslation('title', 'en', false),
            'client_name' => $this->client_name,
            'year' => $this->year,
            'url' => $this->url,
            // Flexible metric map (schema-master) flattened for the editor's rows.
            'results' => collect($this->results ?? [])
                ->map(fn ($value, $label) => ['label' => (string) $label, 'value' => (string) $value])
                ->values()
                ->all(),
            'cover_image_url' => $this->cover_image_url,
            // Recorded, not displayed (ADR-AA) — the editor needs it, the public
            // page never renders it.
            'occurred_on' => $this->occurred_on?->toDateString(),
            // THE WORK, in the owner's chosen order. `is_video` is resolved here
            // so the client never has to parse a mime type.
            'media' => $this->whenLoaded('media', fn () => $this->orderedMedia()->map(fn ($item) => [
                'id' => (int) $item->id,
                'url' => $item->getUrl(),
                'is_video' => str_starts_with((string) $item->mime_type, 'video/'),
            ])->values()->all(), []),
            'credits' => ProjectCreditResource::collection($this->whenLoaded('credits')),
            // The home tab (tag 0) — ADR-Q's single scope, kept in sync.
            'talent_type_id' => $this->talent_type_id !== null ? (int) $this->talent_type_id : null,
            'skill_ids' => $this->whenLoaded('talentTypes', fn () => $this->talentTypes->pluck('id')->map('intval')->values()->all(), []),
            'skills' => $this->whenLoaded('talentTypes', fn () => $this->talentTypes->map(fn (TalentType $type) => [
                'id' => (int) $type->id,
                'slug' => $type->slug,
                'name' => $type->getTranslation('name', $locale, false) ?: $type->getTranslation('name', 'en', false),
                'icon' => $type->icon,
            ])->values()->all(), []),
            // The specialities this project drew on (ADR-Y) — grouped by skill on
            // the client, so the skill id rides along with each one.
            'speciality_ids' => $this->whenLoaded('specialities', fn () => $this->specialities->pluck('id')->map('intval')->values()->all(), []),
            'specialities' => $this->whenLoaded('specialities', fn () => $this->specialities->map(fn (Speciality $speciality) => [
                'id' => (int) $speciality->id,
                'slug' => $speciality->slug,
                'name' => $speciality->getTranslation('name', $locale, false) ?: $speciality->getTranslation('name', 'en', false),
                'talent_type_id' => (int) $speciality->talent_type_id,
                'is_regional' => (bool) $speciality->is_regional,
            ])->values()->all(), []),
        ];
    }
}
