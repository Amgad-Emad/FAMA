<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectCredit;
use App\Models\Speciality;
use App\Models\Talent;
use App\States\ProjectCredit\Declined;
use App\Support\Html\BriefSanitizer;
use App\Support\ProjectBlocks;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

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

        // A new project owns no media yet, so its blocks can point at none
        // (the editor uploads right after Publish, then saves the blocks again).
        $attributes = $this->withBlocks($attributes, []);

        return $this->runInTransaction(function () use ($talent, $attributes, $skillIds, $specialityIds): Project {
            $project = $talent->projects()->create($this->clean($attributes) + [
                'position' => (int) $talent->projects()->max('position') + 1,
                'talent_type_id' => $skillIds[0],
            ]);

            $this->syncSkills($project, $skillIds);
            $this->syncSpecialities($project, $specialityIds);

            // A first project becomes the featured one (a talent with projects
            // always has exactly one featured).
            $this->featured()->ensure($talent);
            app(ProfileCompletionService::class)->notifyIfComplete($talent);

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

        $attributes = $this->withBlocks(
            $attributes,
            $project->media()->where('collection_name', 'media')->pluck('id')->map('intval')->all(),
        );

        return $this->runInTransaction(function () use ($project, $attributes, $skillIds, $specialityIds): Project {
            $project->fill($this->clean($attributes));

            if ($skillIds !== null) {
                $project->talent_type_id = $skillIds[0];
            }

            $project->save();

            if (array_key_exists('blocks', $attributes)) {
                $this->syncStoryMedia($project);
            }

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
     * Delete a project (its tags, media and credits cascade). Only a SOLE owner
     * may delete (co-ownership): while anyone else owns it, deleting would take
     * their work away, so the owner is told to leave instead.
     */
    public function delete(Project $project): void
    {
        if ($project->hasCoOwners()) {
            throw new InvalidArgumentException(__('This project has other owners. Leave it instead.'));
        }

        $this->runInTransaction(function () use ($project): void {
            $owner = $project->talent;
            $project->delete();

            // Deleting the featured project hands the cover to the next one.
            if ($owner !== null) {
                $this->featured()->ensure($owner->refresh());
            }
        }, ['project_id' => $project->id]);
    }

    /**
     * An owner leaves the project (co-ownership).
     *
     *  - A CO-OWNER leaving = their accepted credit goes to `declined` (the same
     *    withdraw transition the Credits page uses, which also notifies the
     *    home owner). The project stays; it comes off their portfolio.
     *  - The CREATOR leaving while co-owners exist = the project is transferred
     *    to the longest-standing co-owner (earliest confirmation). See
     *    transferTo() for what moves.
     *  - The creator as SOLE owner cannot leave — that would orphan the project;
     *    they delete it instead (422).
     */
    public function leave(Project $project, Talent $talent): Project
    {
        if (! $project->isOwnedBy($talent)) {
            throw new InvalidArgumentException(__('You are not an owner of this project.'));
        }

        if ((int) $project->talent_id !== (int) $talent->getKey()) {
            $credit = $project->coOwnerCredits()->firstWhere('talent_id', $talent->getKey());

            app(ProjectCreditService::class)->decline($credit);

            return $project->fresh();
        }

        // Only a co-owner whose account still exists can take the project (a
        // deleted co-owner would make the hand-over fail — or hand it to no one).
        $successor = $project->activeCoOwnerCredits()->first();

        if ($successor === null) {
            throw new InvalidArgumentException(__('You’re the only owner of this project. Delete it instead.'));
        }

        return $this->transferTo($project, $successor);
    }

    /**
     * Hand the project to a co-owner when its creator leaves. One transaction:
     *
     *  1. `talent_id` moves to the successor, appended to the end of THEIR
     *     project ordering.
     *  2. The successor's credit row is KEPT (still `accepted`): it carries their
     *     role and skill line on the project's public Credits card, which is
     *     true work history. Project::coOwnerCredits() ignores a row naming the
     *     home owner, so they never count as their own co-owner, and
     *     portfolioProjects() already shows a project you own AND are credited
     *     on once.
     *  3. Skill tags must belong to the home owner (ADR-X): the tags the
     *     successor holds are kept in order; if none survive, the project is
     *     re-homed on the skill they were credited in (if they hold it), else
     *     their primary skill; a successor with no skills at all leaves the
     *     project skill-less (ADR-Q's preserved-content state) rather than
     *     breaking the transfer. Specialities that no longer hang off a tag are
     *     pruned (ADR-Y).
     *  4. The departing creator keeps no ownership: any credit naming them on
     *     this project is declined, so their name comes off it.
     */
    private function transferTo(Project $project, ProjectCredit $successor): Project
    {
        $from = (int) $project->talent_id;
        $heir = Talent::findOrFail($successor->talent_id);

        return $this->runInTransaction(function () use ($project, $successor, $heir, $from): Project {
            $held = $heir->talentTypes()->get()
                ->sortBy(fn ($type) => [$type->pivot->is_primary ? 0 : 1, (int) $type->pivot->position])
                ->pluck('id')->map('intval')->values();

            $tags = collect($project->skillIds())->filter(fn ($id) => $held->contains($id))->values();

            if ($tags->isEmpty() && $held->isNotEmpty()) {
                $tags = collect([$held->contains((int) $successor->talent_type_id)
                    ? (int) $successor->talent_type_id
                    : $held->first()]);
            }

            $project->talent_id = $heir->getKey();
            $project->talent_type_id = $tags->first();
            $project->position = (int) Project::where('talent_id', $heir->getKey())->max('position') + 1;
            $project->save();

            $this->syncSkills($project, $tags->all());
            $this->pruneSpecialities($project, $tags->all());

            $project->credits()
                ->where('talent_id', $from)
                ->whereIn('status', ['pending', 'accepted'])
                ->get()
                ->each(function (ProjectCredit $credit): void {
                    $credit->status->transitionTo(Declined::class);
                    $credit->forceFill(['responded_at' => now()])->save();
                });

            // The project left the creator's portfolio: both featured picks are re-checked.
            // The leaving creator may be a deleted account (Trash → delete forever): no pick to repair then.
            if ($previous = Talent::find($from)) {
                $this->featured()->ensure($previous);
            }
            $this->featured()->ensure($heir);

            return $project->fresh(['talentTypes', 'specialities']);
        }, ['project_id' => $project->id, 'from' => $from, 'to' => $heir->getKey()]);
    }

    /** The featured-project rules (one home: FeaturedProjectService). */
    private function featured(): FeaturedProjectService
    {
        return app(FeaturedProjectService::class);
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
    public function addMedia(Project $project, array $files, bool $place = true): Project
    {
        return $this->runInTransaction(function () use ($project, $files, $place): Project {
            $added = [];
            foreach ($files as $file) {
                // medialibrary stamps `order_column` as max+1 on add, so an
                // upload lands at the END on its own — no manual ordering here.
                $added[] = (int) $project->addMedia($file)->toMediaCollection('media')->id;
            }

            // A caller that does not place pictures itself (the API, older
            // clients) gets them on the page: added to the last Photos block,
            // or a new one at the end. The block editor places its own (ADR-AN).
            if ($place && is_array($project->blocks)) {
                $project->blocks = $this->appendToGallery($project->blocks, $added);
                $project->save();
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

    /** How long a picture taken out of the story is kept (Undo, a second tab) before it is deleted. */
    private const DETACHED_GRACE_HOURS = 24;

    /**
     * Put new pictures at the end of the story's last Photos block (a new one
     * when there is none, or it is full).
     *
     * @param  list<array<string, mixed>>  $blocks
     * @param  list<int>  $ids
     * @return list<array<string, mixed>>
     */
    private function appendToGallery(array $blocks, array $ids): array
    {
        foreach ($ids as $id) {
            $at = null;
            foreach ($blocks as $i => $block) {
                if (($block['t'] ?? '') === 'gallery') {
                    $at = $i;
                }
            }
            if ($at === null || count($blocks[$at]['items'] ?? []) >= ProjectBlocks::MAX_GALLERY) {
                $blocks[] = ['t' => 'gallery', 'items' => []];
                $at = array_key_last($blocks);
            }
            $blocks[$at]['items'][] = ['m' => $id, 'cap' => ''];
        }

        return $blocks;
    }

    /**
     * Normalise a `blocks` payload (ADR-AN) and write the text projections the
     * rest of the app reads: `summary.en` = the lead, `body.en` = the story
     * after it. Without `blocks`, the payload is left as it came (API writers
     * that still send summary/body keep working).
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $ownedMediaIds
     * @return array<string, mixed>
     */
    private function withBlocks(array $attributes, array $ownedMediaIds): array
    {
        if (! array_key_exists('blocks', $attributes) || $attributes['blocks'] === null) {
            unset($attributes['blocks']);

            return $attributes;
        }

        $blocks = ProjectBlocks::normalize((array) $attributes['blocks'], $ownedMediaIds);
        $lead = ProjectBlocks::summary($blocks);

        $attributes['blocks'] = $blocks;
        $attributes['summary'] = (array) ($attributes['summary'] ?? []);
        $attributes['body'] = (array) ($attributes['body'] ?? []);
        $attributes['summary']['en'] = $lead === '' ? '' : '<p>'.e($lead).'</p>';
        $attributes['body']['en'] = ProjectBlocks::bodyHtml($blocks);

        return $attributes;
    }

    /**
     * Keep the `media` collection in step with the story (ADR-AN):
     *  - the order follows first appearance in the blocks, so the lead image,
     *    cards and feed spreads show what the story opens with;
     *  - a picture no block shows any more is marked `detached_at` (hidden from
     *    every reader) and deleted once the grace period has passed — a save
     *    never destroys a file outright, so the editor's Undo can bring it back;
     *  - a picture shown again loses the mark.
     */
    private function syncStoryMedia(Project $project): void
    {
        $shown = ProjectBlocks::mediaIds($project->blocks ?? []);
        $items = $project->media()->where('collection_name', 'media')->orderBy('order_column')->get();
        $rank = array_flip($shown);
        $cutoff = now()->subHours(self::DETACHED_GRACE_HOURS);
        $position = 0;

        $ordered = $items->sortBy(fn (Media $m) => [isset($rank[$m->id]) ? 0 : 1, $rank[$m->id] ?? $m->order_column])->values();

        foreach ($ordered as $item) {
            $detachedAt = $item->getCustomProperty('detached_at');

            if (isset($rank[$item->id])) {
                if ($detachedAt !== null) {
                    $item->forgetCustomProperty('detached_at');
                }
            } elseif ($detachedAt === null) {
                $item->setCustomProperty('detached_at', now()->toIso8601String());
            } elseif (Carbon::parse($detachedAt)->lt($cutoff)) {
                $item->delete(); // through Eloquent, so the file goes too

                continue;
            }

            $item->order_column = $position++;
            if ($item->isDirty()) {
                $item->save();
            }
        }

        $project->unsetRelation('media');
    }

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

        // `results`: an ordered list of up to four {label, value, compare}
        // rows, blanks dropped (Project::normalizeResults is the one shape).
        if (array_key_exists('results', $attributes)) {
            $attributes['results'] = Project::normalizeResults((array) ($attributes['results'] ?? [])) ?: null;
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
