<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Talent;
use App\Support\Html\BriefSanitizer;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

/**
 * Projects manager (talent-spec §"Selected work") — the talent's own portfolio
 * projects, owned end to end by the dedicated Projects page.
 *
 * The rule this service exists to hold: a project MUST be tagged with at least
 * one of the talent's own skills (ADR-X). The tags live in the
 * `project_talent_type` pivot and drive which skill tabs the project appears in;
 * tag 0 is mirrored onto `projects.talent_type_id` so ADR-Q's single-scope
 * readers (public profile tab scoping, PublicProfileResource) keep working.
 *
 * Every write is one transaction — a project and its tags never land apart.
 * Rich text (summary/body) is sanitized on the way in with the shared allowlist.
 */
class TalentProjectService extends Service
{
    /** Translatable attributes whose HTML is sanitized before storage. */
    private const RICH = ['summary', 'body'];

    /** Every translatable attribute the editor writes. */
    private const TRANSLATABLE = ['title', 'role', 'summary', 'body'];

    /**
     * Create a project for the talent, tagged with the given skills, appended to
     * the end of their ordering.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $skillIds
     */
    public function create(Talent $talent, array $attributes, array $skillIds): Project
    {
        $skillIds = $this->assertOwnedSkills($talent, $skillIds);

        return $this->runInTransaction(function () use ($talent, $attributes, $skillIds): Project {
            $project = $talent->projects()->create($this->clean($attributes) + [
                'position' => (int) $talent->projects()->max('position') + 1,
                'talent_type_id' => $skillIds[0],
            ]);

            $this->syncSkills($project, $skillIds);

            return $project->load('talentTypes');
        }, ['talent_id' => $talent->id, 'skills' => $skillIds]);
    }

    /**
     * Update a project. Skills are only touched when a list is supplied, and an
     * EMPTY list is a domain error — a project can never lose its last skill.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<int>|null  $skillIds
     */
    public function update(Project $project, array $attributes, ?array $skillIds = null): Project
    {
        $skillIds = $skillIds === null
            ? null
            : $this->assertOwnedSkills($project->talent, $skillIds);

        return $this->runInTransaction(function () use ($project, $attributes, $skillIds): Project {
            $project->fill($this->clean($attributes));

            if ($skillIds !== null) {
                $project->talent_type_id = $skillIds[0];
            }

            $project->save();

            if ($skillIds !== null) {
                $this->syncSkills($project, $skillIds);
            }

            return $project->load('talentTypes');
        }, ['project_id' => $project->id, 'skills' => $skillIds]);
    }

    /**
     * Delete a project (its tags and media cascade).
     */
    public function delete(Project $project): void
    {
        $this->runInTransaction(function () use ($project): void {
            $project->delete();
        }, ['project_id' => $project->id]);
    }

    /**
     * Reorder the talent's projects to match the given id order. Any id that is
     * not theirs is a domain error, so a tampered payload changes nothing.
     *
     * @param  list<int>  $orderedIds
     */
    public function reorder(Talent $talent, array $orderedIds): void
    {
        $this->runInTransaction(function () use ($talent, $orderedIds): void {
            $owned = $talent->projects()->pluck('id')->map('intval')->all();

            foreach ($orderedIds as $index => $id) {
                if (! in_array((int) $id, $owned, true)) {
                    throw new InvalidArgumentException(__('That project is not yours.'));
                }

                $talent->projects()->whereKey($id)->update(['position' => $index]);
            }
        }, ['talent_id' => $talent->id]);
    }

    /**
     * Replace the project's cover image (a single-file media collection, so the
     * previous cover is removed by medialibrary).
     */
    public function setCover(Project $project, UploadedFile $file): Project
    {
        return $this->runInTransaction(function () use ($project, $file): Project {
            $project->addMedia($file)->toMediaCollection('cover');

            return $project->fresh(['media', 'talentTypes']);
        }, ['project_id' => $project->id], channel: 'media');
    }

    /**
     * Drop the project's cover image.
     */
    public function removeCover(Project $project): Project
    {
        return $this->runInTransaction(function () use ($project): Project {
            $project->clearMediaCollection('cover');

            return $project->fresh(['media', 'talentTypes']);
        }, ['project_id' => $project->id], channel: 'media');
    }

    // ----- Internal ----------------------------------------------------------

    /**
     * THE skill rule (ADR-X): at least one skill, every one of them the talent's
     * own. Returns the de-duplicated, re-indexed list in the order given — the
     * first entry becomes the project's home tab.
     *
     * @param  list<int>  $skillIds
     * @return list<int>
     */
    private function assertOwnedSkills(Talent $talent, array $skillIds): array
    {
        $skillIds = array_values(array_unique(array_map('intval', $skillIds)));

        if ($skillIds === []) {
            throw new InvalidArgumentException(__('Pick at least one skill for this project.'));
        }

        $owned = $talent->talentTypes()->pluck('talent_types.id')->map('intval')->all();

        if (array_diff($skillIds, $owned) !== []) {
            throw new InvalidArgumentException(__('You can only tag your own skills on a project.'));
        }

        return $skillIds;
    }

    /**
     * Write the tag set, keeping `position` as the given order so tag 0 stays the
     * primary skill mirrored on `talent_type_id`.
     *
     * @param  list<int>  $skillIds
     */
    private function syncSkills(Project $project, array $skillIds): void
    {
        $project->talentTypes()->sync(
            collect($skillIds)->mapWithKeys(fn (int $id, int $i) => [$id => ['position' => $i]])->all()
        );

        $project->unsetRelation('talentTypes');
    }

    /**
     * Sanitize the rich translatable fields and normalise the metric map.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function clean(array $attributes): array
    {
        $attributes = $this->dropEmptyForeignTranslations($attributes);

        foreach (self::RICH as $field) {
            if (! is_array($attributes[$field] ?? null)) {
                continue;
            }

            foreach ($attributes[$field] as $locale => $value) {
                $attributes[$field][$locale] = is_string($value) && $value !== ''
                    ? BriefSanitizer::clean($value)
                    : $value;
            }
        }

        // `results` is a flexible metric map (schema-master) — the editor submits
        // a label/value list, stored as a compact map with the blanks dropped.
        if (isset($attributes['results']) && is_array($attributes['results'])) {
            $attributes['results'] = collect($attributes['results'])
                ->filter(fn ($row) => filled($row['label'] ?? null) && filled($row['value'] ?? null))
                ->mapWithKeys(fn ($row) => [(string) $row['label'] => (string) $row['value']])
                ->all() ?: null;
        }

        return $attributes;
    }

    /**
     * Drop blank NON-ENGLISH translations from the payload.
     *
     * V1 has no Arabic inputs in the editor, so the composer submits `ar => ''`.
     * Storing that empty string is worse than storing nothing: spatie returns the
     * blank rather than falling back, so the title would render EMPTY on /ar.
     * Removing the key leaves the locale genuinely absent (fallback works) and —
     * because spatie's setTranslations() merges per locale rather than replacing
     * the map — an Arabic value stored earlier survives the edit untouched.
     *
     * A blank ENGLISH value is left alone on purpose: that input is on screen, so
     * clearing it must actually clear the translation.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function dropEmptyForeignTranslations(array $attributes): array
    {
        foreach (self::TRANSLATABLE as $field) {
            if (! is_array($attributes[$field] ?? null)) {
                continue;
            }

            foreach ($attributes[$field] as $locale => $value) {
                if ($locale !== 'en' && ($value === null || $value === '')) {
                    unset($attributes[$field][$locale]);
                }
            }
        }

        return $attributes;
    }
}
