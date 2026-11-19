<?php

namespace App\Http\Resources;

use App\Models\ProjectCredit;
use Illuminate\Http\Request;

/**
 * One collaborator credit (ADR-AA).
 *
 * `is_public` is computed from the STATE rather than restated as a rule here —
 * the state machine is the single answer to "may this be shown", and duplicating
 * that test in a resource is how the two drift apart.
 *
 * @mixin ProjectCredit
 */
class ProjectCreditResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'name' => $this->displayName(),
            'role' => $this->role,
            'status' => (string) $this->status,
            'is_hidden' => $this->hidden_at !== null,
            'is_public' => $this->status->isPublic(),
            'is_on_platform' => $this->isOnPlatform(),
            'position' => (int) $this->position,
            'responded_at' => $this->responded_at?->toIso8601String(),
            'talent' => $this->whenLoaded('talent', fn () => $this->talent === null ? null : [
                'id' => (int) $this->talent->id,
                'slug' => $this->talent->slug,
                'display_name' => $this->talent->display_name,
                'avatar_url' => $this->talent->avatar_url,
            ]),
            'skill' => $this->whenLoaded('talentType', fn () => [
                'id' => (int) $this->talentType->id,
                'slug' => $this->talentType->slug,
                'name' => $this->talentType->getTranslation('name', $locale, false)
                    ?: $this->talentType->getTranslation('name', 'en', false),
                'icon' => $this->talentType->icon,
            ]),
            // Present on the credited person's own "Credits" page, where the
            // project is someone ELSE's and needs naming.
            'project' => $this->whenLoaded('project', fn () => [
                'id' => (int) $this->project->id,
                'title' => (string) $this->project->title,
                'cover_image_url' => $this->project->lead_image_url,
                'owner' => $this->project->relationLoaded('talent') && $this->project->talent !== null ? [
                    'slug' => $this->project->talent->slug,
                    'display_name' => $this->project->talent->display_name,
                ] : null,
            ]),
        ];
    }
}
