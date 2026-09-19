<?php

namespace App\Services;

use App\Models\Speciality;
use App\Models\Talent;
use App\Models\TalentInterest;
use App\Models\TalentType;
use InvalidArgumentException;

/**
 * "Willing to explore" — the directions a talent wants to move toward (ADR-Z).
 *
 * The ONLY write path. The one rule that matters lives in assertShape(): a
 * speciality, when given, must belong to the chosen skill. Note what is
 * deliberately NOT enforced — the SKILL need not be one the talent already
 * holds. Wanting to move toward a discipline you do not yet list is the entire
 * point of this section, so borrowing the ownership rule from
 * SkillsService::setSpecialities() here would defeat it.
 */
class TalentInterestService extends Service
{
    /** A page of interests is short by design; more reads as a wish list. */
    private const MAX = 6;

    /**
     * Add a direction, appended to the end of the talent's ordering.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(Talent $talent, array $data): TalentInterest
    {
        [$type, $speciality] = $this->assertShape($data);

        return $this->runInTransaction(function () use ($talent, $type, $speciality, $data): TalentInterest {
            if ($talent->interests()->count() >= self::MAX) {
                throw new InvalidArgumentException(
                    __('You can list up to :max directions. Remove one to add another.', ['max' => self::MAX])
                );
            }

            // The same skill + kind of work twice is a duplicate, not a stronger
            // signal. Scoped to the pair so "Photography" and "Photography ·
            // Food" can legitimately coexist.
            $exists = $talent->interests()
                ->where('talent_type_id', $type->getKey())
                ->where('speciality_id', $speciality?->getKey())
                ->exists();

            if ($exists) {
                throw new InvalidArgumentException(__('That direction is already on your profile.'));
            }

            return $talent->interests()->create([
                'talent_type_id' => $type->getKey(),
                'speciality_id' => $speciality?->getKey(),
                'note' => $data['note'] ?? null,
                'position' => (int) $talent->interests()->max('position') + 1,
            ]);
        }, ['talent_id' => $talent->id]);
    }

    /**
     * Edit a direction in place.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(TalentInterest $interest, array $data): TalentInterest
    {
        [$type, $speciality] = $this->assertShape($data);

        return $this->runInTransaction(function () use ($interest, $type, $speciality, $data): TalentInterest {
            $interest->update([
                'talent_type_id' => $type->getKey(),
                'speciality_id' => $speciality?->getKey(),
                'note' => $data['note'] ?? null,
            ]);

            return $interest->refresh();
        }, ['interest_id' => $interest->id]);
    }

    public function delete(TalentInterest $interest): void
    {
        $this->runInTransaction(function () use ($interest): void {
            $interest->delete();
        }, ['interest_id' => $interest->id]);
    }

    /**
     * Reorder the talent's directions. An id that is not theirs is a domain
     * error, so a tampered payload changes nothing.
     *
     * @param  list<int>  $orderedIds
     */
    public function reorder(Talent $talent, array $orderedIds): void
    {
        $this->runInTransaction(function () use ($talent, $orderedIds): void {
            $owned = $talent->interests()->pluck('id')->map('intval')->all();

            foreach ($orderedIds as $position => $id) {
                if (! in_array((int) $id, $owned, true)) {
                    throw new InvalidArgumentException("Interest [{$id}] does not belong to this talent.");
                }

                TalentInterest::whereKey($id)->update(['position' => $position]);
            }
        }, ['talent_id' => $talent->id]);
    }

    /**
     * Resolve and validate the skill + optional speciality pair.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: TalentType, 1: Speciality|null}
     */
    private function assertShape(array $data): array
    {
        $type = TalentType::find($data['talent_type_id'] ?? null)
            ?? throw new InvalidArgumentException(__('Pick a skill for this direction.'));

        $specialityId = $data['speciality_id'] ?? null;

        if ($specialityId === null || $specialityId === '') {
            return [$type, null];
        }

        $speciality = Speciality::find($specialityId);

        if ($speciality === null || (int) $speciality->talent_type_id !== (int) $type->getKey()) {
            throw new InvalidArgumentException(
                __('That kind of work does not belong to the skill you picked.')
            );
        }

        return [$type, $speciality];
    }
}
