<?php

namespace App\Services;

use App\Actions\SeedBlocksForSkill;
use App\Models\PortfolioItem;
use App\Models\Speciality;
use App\Models\Talent;
use App\Models\TalentType;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Skills manager (talent-spec workflow #11). Add/remove skills and reorder.
 * There is no separately chosen primary skill (fama-ui, 2026-10-12): the ORDER
 * is the profile's tab order and the first skill opens first. `is_primary` stays
 * on the pivot for compatibility but is DERIVED — syncPrimaryFromOrder() sets it
 * on the position-0 skill only, inside every transaction that changes the order. Adding a skill seeds THAT skill's default blocks into its own tab
 * (per-skill, ADR-Q). Removing a skill deletes its tab's blocks but preserves the
 * underlying content (gallery items are un-linked, projects are un-scoped). Skills
 * persist through the `talent_types` catalog (see schema-master).
 */
class SkillsService extends Service
{
    public function __construct(private readonly SeedBlocksForSkill $seedBlocksForSkill) {}

    /**
     * Link a skill to the talent at the END of the order and seed its own tab's
     * default blocks. `$first` puts it at the front instead (it then opens first).
     * The derived primary flag is re-synced in the same transaction.
     */
    public function addType(Talent $talent, TalentType $type, bool $first = false): void
    {
        $this->runInTransaction(function () use ($talent, $type, $first): void {
            if ($talent->talentTypes()->whereKey($type->id)->exists()) {
                throw new InvalidArgumentException("Talent already works as [{$type->slug}].");
            }

            $position = (int) $talent->talentTypes()->newPivotStatement()
                ->where('talent_id', $talent->id)->max('position');

            $talent->talentTypes()->attach($type->id, [
                'is_primary' => false,
                'position' => $talent->talentTypes()->exists() ? $position + 1 : 0,
            ]);

            $talent->load('talentTypes');
            ($this->seedBlocksForSkill)($talent, $type);

            if ($first) {
                $this->moveToFront($talent, $type->id);
            }

            $this->syncPrimaryFromOrder($talent);
        }, ['talent_id' => $talent->id, 'type' => $type->slug]);
    }

    /**
     * Unlink a skill from the talent. Deletes that skill's tab blocks but PRESERVES
     * content (ADR-Q): gallery items in those blocks lose their `block_id` and the
     * skill's projects are un-scoped (`talent_type_id` → NULL). The removal is
     * logged (the UI requires an explicit confirmation first).
     */
    public function removeType(Talent $talent, TalentType $type): void
    {
        // Removing a skill drops its specialities — possibly the last one, which
        // is a Go Live step (ADR-AC). Capture readiness before the write.
        $wasReady = app(ProfileCompletionService::class)->canGoLive($talent);

        $this->runInTransaction(function () use ($talent, $type): void {
            // Preserve project content — drop this skill's tag from the talent's
            // projects (ADR-X) and re-home each one on its first remaining tag.
            // A project left with no tags becomes un-scoped (ADR-Q) rather than
            // being deleted: the write-up survives the skill.
            $this->untagProjects($talent, $type);

            // Preserve gallery content — detach items from this skill's blocks, then
            // delete the blocks (the FK is nullOnDelete; this is explicit + logged).
            $blockIds = $talent->profileBlocks()->where('talent_type_id', $type->id)->pluck('id');
            if ($blockIds->isNotEmpty()) {
                PortfolioItem::whereIn('block_id', $blockIds)->update(['block_id' => null]);
                $talent->profileBlocks()->whereKey($blockIds)->delete();
            }

            // A speciality only exists under a skill, so claims to this skill's
            // specialities go with it. Nothing is preserved here on purpose: a
            // speciality claim carries no content, and leaving orphaned claims
            // behind would put a talent in a discovery filter for a skill they
            // no longer hold.
            $specialities = $talent->specialities()
                ->where('specialities.talent_type_id', $type->id)
                ->pluck('specialities.id');

            if ($specialities->isNotEmpty()) {
                $talent->specialities()->detach($specialities);
            }

            $talent->talentTypes()->detach($type->id);

            // The next skill in the order now opens first.
            $this->syncPrimaryFromOrder($talent);

            Log::channel('app')->info('Talent removed a skill (content preserved).', [
                'talent_id' => $talent->id,
                'talent_type_id' => $type->id,
                'skill' => $type->slug,
                'blocks_deleted' => $blockIds->count(),
                'specialities_detached' => $specialities->count(),
            ]);
        }, ['talent_id' => $talent->id, 'type' => $type->slug]);

        app(ProfileCompletionService::class)->syncAfterWrite($talent, $wasReady);
    }

    /**
     * Kept for compatibility (the route stays): "make primary" now means "move
     * to the front of the order", and the derived flag follows the order.
     */
    public function setPrimary(Talent $talent, TalentType $type): void
    {
        $this->runInTransaction(function () use ($talent, $type): void {
            if (! $talent->talentTypes()->whereKey($type->id)->exists()) {
                throw new InvalidArgumentException("Talent does not work as [{$type->slug}].");
            }

            $this->moveToFront($talent, $type->id);
            $this->syncPrimaryFromOrder($talent);
        }, ['talent_id' => $talent->id, 'type' => $type->slug]);
    }

    /**
     * Reorder the talent's skills to match the given type-id order.
     *
     * @param  list<int>  $orderedTypeIds
     */
    public function reorderTypes(Talent $talent, array $orderedTypeIds): void
    {
        $this->runInTransaction(function () use ($talent, $orderedTypeIds): void {
            $owned = $talent->talentTypes()->pluck('talent_types.id')->all();

            foreach ($orderedTypeIds as $index => $id) {
                if (! in_array($id, $owned, true)) {
                    throw new InvalidArgumentException("Type [{$id}] is not linked to this talent.");
                }

                $talent->talentTypes()->updateExistingPivot($id, ['position' => $index]);
            }

            $this->syncPrimaryFromOrder($talent);
        }, ['talent_id' => $talent->id]);
    }

    /**
     * Derive the primary flag from the order: true on the first skill (lowest
     * position, ties by id), false on every other. Call inside the transaction
     * that changed the order.
     */
    public function syncPrimaryFromOrder(Talent $talent): void
    {
        $pivot = fn () => $talent->talentTypes()->newPivotStatement()->where('talent_id', $talent->id);

        $firstId = $pivot()->orderBy('position')->orderBy('talent_type_id')->value('talent_type_id');

        $pivot()->update(['is_primary' => false]);

        if ($firstId !== null) {
            $pivot()->where('talent_type_id', $firstId)->update(['is_primary' => true]);
        }
    }

    /**
     * Rewrite the positions 0..n with one skill moved to the front.
     */
    private function moveToFront(Talent $talent, int $typeId): void
    {
        $order = $talent->talentTypes()->newPivotStatement()
            ->where('talent_id', $talent->id)
            ->orderBy('position')->orderBy('talent_type_id')
            ->pluck('talent_type_id')
            ->reject(fn ($id) => (int) $id === $typeId)
            ->prepend($typeId)
            ->values();

        foreach ($order as $index => $id) {
            $talent->talentTypes()->newPivotStatement()
                ->where('talent_id', $talent->id)->where('talent_type_id', $id)
                ->update(['position' => $index]);
        }
    }

    /**
     * Replace the talent's speciality claims UNDER ONE SKILL with the given set
     * (ADR-Y). Scoped to a single skill so the picker for one skill can never
     * silently wipe the claims made under another.
     *
     * Two rules are enforced here and nowhere else: the talent must hold the
     * skill, and every speciality must belong to THAT skill and still be
     * selectable. That second rule is what keeps the vocabulary controlled — a
     * client that posts an arbitrary id gets a 422, not a cross-skill claim.
     *
     * Passing an empty list is a legitimate "I do all of it / none in
     * particular" and clears the skill's claims.
     *
     * @param  list<int>  $specialityIds  ordered; the order is what the profile renders
     */
    public function setSpecialities(Talent $talent, TalentType $type, array $specialityIds): void
    {
        // A skill with a speciality is a Go Live step (ADR-AC): capture readiness
        // before the write so clearing the last one takes the profile out of Live.
        $wasReady = app(ProfileCompletionService::class)->canGoLive($talent);

        $this->runInTransaction(function () use ($talent, $type, $specialityIds): void {
            if (! $talent->talentTypes()->whereKey($type->id)->exists()) {
                throw new InvalidArgumentException("Talent does not work as [{$type->slug}].");
            }

            $ids = collect($specialityIds)->map('intval')->unique()->values();

            $valid = Speciality::query()
                ->selectable()
                ->where('talent_type_id', $type->id)
                ->whereIn('id', $ids)
                ->pluck('id');

            $rejected = $ids->diff($valid);
            if ($rejected->isNotEmpty()) {
                throw new InvalidArgumentException(
                    __('These specialities do not belong to :skill: :ids', [
                        'skill' => $type->slug,
                        'ids' => $rejected->implode(', '),
                    ])
                );
            }

            // Detach only THIS skill's claims, then re-attach in the given order.
            $existing = $talent->specialities()
                ->where('specialities.talent_type_id', $type->id)
                ->pluck('specialities.id');

            if ($existing->isNotEmpty()) {
                $talent->specialities()->detach($existing);
            }

            foreach ($ids as $position => $id) {
                $talent->specialities()->attach($id, ['position' => $position]);
            }
        }, ['talent_id' => $talent->id, 'type' => $type->slug]);

        app(ProfileCompletionService::class)->syncAfterWrite($talent, $wasReady);
    }

    /**
     * Remove a skill's tag from every one of the talent's projects and re-home
     * each affected project on its first remaining tag (NULL when none is left,
     * which parks it in the universal region — ADR-Q/ADR-X).
     */
    private function untagProjects(Talent $talent, TalentType $type): void
    {
        $projects = $talent->projects()
            ->whereHas('talentTypes', fn ($q) => $q->whereKey($type->id))
            ->with('talentTypes')
            ->get();

        foreach ($projects as $project) {
            $project->talentTypes()->detach($type->id);

            $remaining = $project->talentTypes()->pluck('talent_types.id')->map('intval')->all();

            $project->forceFill(['talent_type_id' => $remaining[0] ?? null])->save();
        }
    }
}
