<?php

namespace App\Http\Resources;

use App\Models\Project;
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
            // The home tab (tag 0) — ADR-Q's single scope, kept in sync.
            'talent_type_id' => $this->talent_type_id !== null ? (int) $this->talent_type_id : null,
            'skill_ids' => $this->whenLoaded('talentTypes', fn () => $this->talentTypes->pluck('id')->map('intval')->values()->all(), []),
            'skills' => $this->whenLoaded('talentTypes', fn () => $this->talentTypes->map(fn (TalentType $type) => [
                'id' => (int) $type->id,
                'slug' => $type->slug,
                'name' => $type->getTranslation('name', $locale, false) ?: $type->getTranslation('name', 'en', false),
                'icon' => $type->icon,
            ])->values()->all(), []),
        ];
    }
}
