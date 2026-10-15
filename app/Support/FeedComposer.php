<?php

namespace App\Support;

use App\Models\Talent;
use App\Models\TalentType;
use Illuminate\Support\Collection;

/**
 * The New post dialog's server-side config (fama-ui composer): the talent's
 * own Skills, shareable Projects, the full #tag skill catalog and whether the
 * profile is published (project shares need it). One builder for every page
 * that mounts the composer — the feed, the post page (Edit), Activity (Edit).
 * Talent-only (ADR-F1); everyone else gets null.
 */
class FeedComposer
{
    /**
     * The composer config for a viewer, or null when they cannot post.
     *
     * @return array<string, mixed>|null
     */
    public static function for(mixed $viewer): ?array
    {
        if (! $viewer instanceof Talent) {
            return null;
        }

        return [
            'skills' => $viewer->talentTypes()->get(['talent_types.id', 'name', 'slug'])
                ->map(fn ($skill) => ['id' => $skill->id, 'name' => $skill->name, 'slug' => $skill->slug]),
            'projects' => $viewer->projects()->latest('id')->get()
                ->map(fn ($project) => [
                    'id' => $project->id,
                    'title' => $project->title,
                    'cover' => $project->cover_image_url,
                    'year' => $project->year,
                    'client' => $project->client_name,
                ]),
            'username' => $viewer->feedUsername(),
            // "#" tags any skill (own skills save as the post's skill_ids —
            // ADR-F1 keeps those own-only), matched by name or speciality.
            'catalog' => self::catalog($viewer),
            'published' => (bool) $viewer->is_published,
        ];
    }

    /**
     * Every skill as a composer #tag: id, name, slug, CamelCase tag, whether
     * the talent holds it, and its selectable specialities (for "via" hits).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function catalog(Talent $viewer): Collection
    {
        $own = $viewer->talentTypes()->pluck('talent_types.id')->all();

        return TalentType::query()
            ->with(['specialities' => fn ($query) => $query->selectable()])
            ->orderBy('id')
            ->get()
            ->map(fn (TalentType $type) => [
                'id' => $type->id,
                'name' => $type->name,
                'slug' => $type->slug,
                'tag' => SkillTags::tag((string) $type->getTranslation('name', 'en')),
                'own' => in_array($type->id, $own, true),
                'specs' => $type->specialities->pluck('name')->values(),
            ]);
    }
}
