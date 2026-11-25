<?php

namespace App\Services;

use App\Models\Review;
use App\Models\Talent;
use App\States\Review\Approved;
use App\States\Review\Rejected;
use App\States\TalentProfile\Live;
use App\States\TalentProfile\Unpublished;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

/**
 * Orchestrates talent profile management (talent-spec workflows #10, #16–#20):
 * core identity fields, the pricing rate, publish/unpublish, and reviews
 * moderation. State changes go through the relevant state machine; every
 * multi-write op is transactional + fail-logged.
 */
class TalentProfileService extends Service
{
    // ----- Core identity -----------------------------------------------------

    /**
     * Update core identity fields (name, headline, bio, worldview, slug, location).
     * The booking method/value pair is no longer editable here (removed from
     * the V1 editor); existing column data is preserved untouched.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateCore(Talent $talent, array $data): Talent
    {
        // Name and city are Go Live steps: capture readiness BEFORE the write so
        // a save that empties one takes the profile out of Live (ADR-AC).
        $wasReady = $this->completion()->canGoLive($talent);

        $talent = $this->runInTransaction(function () use ($talent, $data): Talent {
            $talent->fill(Arr::only($data, [
                'display_name', 'headline', 'bio', 'worldview', 'slug',
                'base_city', 'base_country',
            ]));
            $talent->save();

            return $talent;
        }, ['talent_id' => $talent->id]);

        $this->completion()->syncAfterWrite($talent, $wasReady);

        return $talent;
    }

    /**
     * Set (or clear) the indicative pricing rate (ADR-N). The three fields are
     * all-or-nothing: a blank amount clears the whole rate; otherwise the currency
     * is normalised to upper-case ISO. Validation lives in UpdatePricingRateRequest.
     *
     * @param  array<string, mixed>  $data
     */
    public function updatePricingRate(Talent $talent, array $data): Talent
    {
        return $this->runInTransaction(function () use ($talent, $data): Talent {
            $amount = $data['rate_amount'] ?? null;

            if ($amount === null || $amount === '') {
                $talent->fill(['rate_unit' => null, 'rate_amount' => null, 'rate_currency' => null]);
            } else {
                $talent->fill([
                    'rate_unit' => $data['rate_unit'],
                    'rate_amount' => $amount,
                    'rate_currency' => strtoupper($data['rate_currency']),
                ]);
            }

            $talent->save();

            return $talent;
        }, ['talent_id' => $talent->id]);
    }

    // ----- Avatar (profile image) --------------------------------------------

    /**
     * Replace the talent's profile image. The `avatar` collection is single-file,
     * so uploading a new one clears the previous automatically (media channel).
     */
    public function updateAvatar(Talent $talent, UploadedFile $file): Talent
    {
        return $this->runInTransaction(function () use ($talent, $file): Talent {
            $talent->addMedia($file)->toMediaCollection('avatar');
            $talent->refresh();
            $this->completion()->notifyIfComplete($talent);

            return $talent;
        }, ['talent_id' => $talent->id], channel: 'media');
    }

    /**
     * Remove the talent's profile image (clears the `avatar` collection).
     */
    public function removeAvatar(Talent $talent): Talent
    {
        return $this->runInTransaction(function () use ($talent): Talent {
            $talent->clearMediaCollection('avatar');

            return $talent->refresh();
        }, ['talent_id' => $talent->id], channel: 'media');
    }

    /**
     * The NOW strip (ADR-Z): the two time-sensitive toggles.
     *
     * `availability_changed_at` is stamped ONLY when `is_available` actually
     * flips. That is the whole point of the field — the public profile shows
     * when availability was last confirmed, and re-saving an unchanged toggle
     * must not refresh that date into a lie. Travel is not time-sensitive in the
     * same way and carries no stamp.
     *
     * `is_available` is INFORMATION, never a gate: ADR-L removed availability
     * gating from enquiries and that still stands.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateNow(Talent $talent, array $data): Talent
    {
        return $this->runInTransaction(function () use ($talent, $data): Talent {
            if (array_key_exists('will_travel', $data)) {
                $talent->will_travel = (bool) $data['will_travel'];
            }

            if (array_key_exists('is_available', $data)) {
                // Assign FIRST, then ask Eloquent whether it actually changed.
                // Comparing against `$talent->is_available` by hand is wrong on a
                // model whose attribute was never hydrated (a fresh insert relying
                // on the column default, a `select()`ed subset): the unset
                // attribute reads as null, `(bool) null` is false, and flipping to
                // "not available" would compare false !== false and silently save
                // nothing. isDirty() consults the ORIGINAL attributes and treats a
                // newly-known value as a change, which is what we want.
                $talent->is_available = (bool) $data['is_available'];

                if ($talent->isDirty('is_available')) {
                    $talent->availability_changed_at = now();
                }
            }

            $talent->save();

            return $talent;
        }, ['talent_id' => $talent->id]);
    }

    /**
     * Replace the talent's social handles with the given ordered list (ADR-Z).
     *
     * A whole-list replace rather than per-row CRUD: the editor renders a short
     * reorderable list and saves it as one, and (talent_id, platform) is unique,
     * so an incremental write would have to reconcile against that constraint
     * on every keystroke.
     *
     * @param  list<array<string, mixed>>  $handles
     */
    public function updateSocialHandles(Talent $talent, array $handles): Talent
    {
        return $this->runInTransaction(function () use ($talent, $handles): Talent {
            $talent->socialHandles()->delete();

            foreach (array_values($handles) as $position => $handle) {
                $talent->socialHandles()->create([
                    'platform' => $handle['platform'],
                    'handle' => $handle['handle'],
                    'url' => $handle['url'],
                    'position' => $position,
                ]);
            }

            return $talent->load('socialHandles');
        }, ['talent_id' => $talent->id, 'count' => count($handles)]);
    }

    /**
     * Go Live (Private → Live; guarded by ToLive) — the talent's own call
     * (ADR-AQ: Draft → Private → Live). Saving the Go Live steps only makes a
     * profile Private; this is the one step that makes its link work. A Draft
     * whose steps are all saved (grandfathered data) may go Live directly.
     * Already Live → no-op. The pre-check gives the talent a clear message; the
     * ToLive guard (display name) is the hard backstop.
     */
    public function publish(Talent $talent): Talent
    {
        if (! $this->completion()->canGoLive($talent)) {
            throw new \InvalidArgumentException(
                __('Finish the Go Live steps first: a display name, a skill with a speciality, and your city.')
            );
        }

        if ($talent->status instanceof Live) {
            return $talent;
        }

        $talent = $this->runInTransaction(function () use ($talent): Talent {
            $talent->status->transitionTo(Live::class);

            return $talent->refresh();
        }, ['talent_id' => $talent->id]);

        $this->completion()->notifyIfComplete($talent);

        return $talent;
    }

    /**
     * Persist where the universal/profile-level section sits among the skill
     * sections (talents.meta['universal_position']). 0 = above the skill tabs
     * (the ADR-R default); any higher index renders the universal blocks BELOW
     * the tab region on the public profile.
     */
    public function updateUniversalPosition(Talent $talent, int $position): Talent
    {
        return $this->runInTransaction(function () use ($talent, $position): Talent {
            $talent->meta = array_merge($talent->meta ?? [], ['universal_position' => $position]);
            $talent->save();

            return $talent;
        }, ['talent_id' => $talent->id]);
    }

    /**
     * Make the profile Private (Live → Unpublished) — the talent's own call,
     * reversible with {@see publish()}. Already Private → no-op. A Draft can't
     * be "made private": it isn't public yet, and Private means its Go Live
     * steps are done (ADR-AQ), so that is refused with a clear message.
     */
    public function unpublish(Talent $talent): Talent
    {
        if ($talent->status instanceof Unpublished) {
            return $talent;
        }

        if (! $talent->status instanceof Live) {
            throw new \InvalidArgumentException(__('Only a Live profile can be made private.'));
        }

        return $this->runInTransaction(function () use ($talent): Talent {
            $talent->status->transitionTo(Unpublished::class);

            return $talent->refresh();
        }, ['talent_id' => $talent->id]);
    }

    // ----- Reviews moderation ------------------------------------------------

    /**
     * Approve a pending review (Pending → Approved).
     */
    public function approveReview(Review $review): Review
    {
        return $this->transitionIfPossible($review, 'status', Approved::class, ['review_id' => $review->id]);
    }

    /**
     * Reject/hide a review (Pending/Approved → Rejected).
     */
    public function rejectReview(Review $review): Review
    {
        return $this->transitionIfPossible($review, 'status', Rejected::class, ['review_id' => $review->id]);
    }

    // ----- Internal ----------------------------------------------------------

    /**
     * Transition a model's state field to $target when allowed (else no-op), in a
     * transaction with fail-logging. Returns the fresh model.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  TModel  $model
     * @param  class-string  $target
     * @param  array<string, mixed>  $context
     * @return TModel
     */
    private function transitionIfPossible($model, string $field, string $target, array $context)
    {
        return $this->runInTransaction(function () use ($model, $field, $target) {
            if ($model->{$field}->canTransitionTo($target)) {
                $model->{$field}->transitionTo($target);
            }

            return $model->refresh();
        }, $context);
    }

    /** The profile path (Go Live / Stand out) — syncs Live and sends the "you're Live" e-mail once. */
    private function completion(): ProfileCompletionService
    {
        return app(ProfileCompletionService::class);
    }
}
