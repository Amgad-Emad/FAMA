<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Speciality;
use App\Models\Talent;
use App\Support\Html\BriefSanitizer;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
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
     * @param  list<int>  $specialityIds  must belong to the tagged skills (ADR-Y)
     */
    public function create(Talent $talent, array $attributes, array $skillIds, array $specialityIds = []): Project
    {
        $skillIds = $this->assertOwnedSkills($talent, $skillIds);
        $specialityIds = $this->assertSkillSpecialities($skillIds, $specialityIds);

        return $this->runInTransaction(function () use ($talent, $attributes, $skillIds, $specialityIds): Project {
            $project = $talent->projects()->create($this->clean($attributes) + [
                'position' => (int) $talent->projects()->max('position') + 1,
                'talent_type_id' => $skillIds[0],
            ]);

            $this->syncSkills($project, $skillIds);
            $this->syncSpecialities($project, $specialityIds);

            return $project->load(['talentTypes', 'specialities']);
        }, ['talent_id' => $talent->id, 'skills' => $skillIds]);
    }

    /**
     * Update a project. Skills are only touched when a list is supplied, and an
     * EMPTY list is a domain error — a project can never lose its last skill.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<int>|null  $skillIds
     * @param  list<int>|null  $specialityIds  null leaves the current picks alone
     */
    public function update(Project $project, array $attributes, ?array $skillIds = null, ?array $specialityIds = null): Project
    {
        $skillIds = $skillIds === null
            ? null
            : $this->assertOwnedSkills($project->talent, $skillIds);

        // Specialities are validated against the skill set the project will END
        // UP with, not the one it had: re-tagging a project's skills and its
        // specialities in one request has to be consistent, and dropping a skill
        // must drop the picks that hung off it rather than orphan them.
        if ($specialityIds !== null) {
            $specialityIds = $this->assertSkillSpecialities(
                $skillIds ?? $project->skillIds(),
                $specialityIds,
            );
        }

        return $this->runInTransaction(function () use ($project, $attributes, $skillIds, $specialityIds): Project {
            $project->fill($this->clean($attributes));

            if ($skillIds !== null) {
                $project->talent_type_id = $skillIds[0];
            }

            $project->save();

            if ($skillIds !== null) {
                $this->syncSkills($project, $skillIds);
            }

            if ($specialityIds !== null) {
                $this->syncSpecialities($project, $specialityIds);
            } elseif ($skillIds !== null) {
                // The skills changed but the picks were not restated — drop any
                // that no longer hang off a tagged skill.
                $this->pruneSpecialities($project, $skillIds);
            }

            return $project->load(['talentTypes', 'specialities']);
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

    /**
     * Append uploaded work to the project's `media` collection (ADR-AA).
     *
     * New items go to the END of the existing order: an upload must never
     * silently displace the lead frame the talent chose.
     *
     * @param  list<UploadedFile>  $files
     */
    public function addMedia(Project $project, array $files): Project
    {
        return $this->runInTransaction(function () use ($project, $files): Project {
            foreach ($files as $file) {
                // medialibrary stamps `order_column` as max+1 on add, so an
                // upload lands at the END on its own — no manual ordering here.
                $project->addMedia($file)->toMediaCollection('media');
            }

            return $project->fresh(['media', 'talentTypes', 'specialities']);
        }, ['project_id' => $project->id, 'count' => count($files)], channel: 'media');
    }

    /**
     * Reorder the project's work. THE ordering rule (ADR-AA): the first frame is
     * doing the most work, so it is chosen, not defaulted. An id outside this
     * project's `media` collection is a domain error, so a tampered payload
     * changes nothing.
     *
     * @param  list<int>  $orderedIds
     */
    public function reorderMedia(Project $project, array $orderedIds): Project
    {
        return $this->runInTransaction(function () use ($project, $orderedIds): Project {
            $owned = $project->media()->where('collection_name', 'media')
                ->pluck('id')->map('intval')->all();

            foreach ($orderedIds as $position => $id) {
                if (! in_array((int) $id, $owned, true)) {
                    throw new InvalidArgumentException("Media [{$id}] is not on this project.");
                }

                Media::whereKey($id)->update(['order_column' => $position]);
            }

            return $project->fresh(['media', 'talentTypes', 'specialities']);
        }, ['project_id' => $project->id], channel: 'media');
    }

    /**
     * Remove one item of work.
     */
    public function removeMedia(Project $project, int $mediaId): Project
    {
        return $this->runInTransaction(function () use ($project, $mediaId): Project {
            $media = $project->media()
                ->where('collection_name', 'media')
                ->whereKey($mediaId)
                ->first() ?? throw new InvalidArgumentException(__('That file is not on this project.'));

            $media->delete();

            return $project->fresh(['media', 'talentTypes', 'specialities']);
        }, ['project_id' => $project->id, 'media_id' => $mediaId], channel: 'media');
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
     * THE speciality rule (ADR-Y): every pick must belong to one of the skills
     * the project is tagged with, and still be selectable. Returns the
     * de-duplicated list in the order given.
     *
     * @param  list<int>  $skillIds
     * @param  list<int>  $specialityIds
     * @return list<int>
     */
    private function assertSkillSpecialities(array $skillIds, array $specialityIds): array
    {
        $specialityIds = array_values(array_unique(array_map('intval', $specialityIds)));

        if ($specialityIds === []) {
            return [];
        }

        $valid = Speciality::query()
            ->selectable()
            ->whereIn('talent_type_id', $skillIds)
            ->whereIn('id', $specialityIds)
            ->pluck('id')
            ->map('intval')
            ->all();

        if (array_diff($specialityIds, $valid) !== []) {
            throw new InvalidArgumentException(
                __('Every speciality must belong to one of the skills you tagged.')
            );
        }

        return $specialityIds;
    }

    /**
     * Write the speciality set, keeping `position` as the given order.
     *
     * @param  list<int>  $specialityIds
     */
    private function syncSpecialities(Project $project, array $specialityIds): void
    {
        $project->specialities()->sync(
            collect($specialityIds)->mapWithKeys(fn (int $id, int $i) => [$id => ['position' => $i]])->all()
        );

        $project->unsetRelation('specialities');
    }

    /**
     * Drop the picks that no longer hang off any of the project's skills.
     *
     * @param  list<int>  $skillIds
     */
    private function pruneSpecialities(Project $project, array $skillIds): void
    {
        $orphaned = $project->specialities()
            ->whereNotIn('specialities.talent_type_id', $skillIds)
            ->pluck('specialities.id');

        if ($orphaned->isNotEmpty()) {
            $project->specialities()->detach($orphaned);
            $project->unsetRelation('specialities');
        }
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
