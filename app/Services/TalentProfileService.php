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
     * Update core identity fields (name, headline, bio, slug, location, booking).
     *
     * @param  array<string, mixed>  $data
     */
    public function updateCore(Talent $talent, array $data): Talent
    {
        return $this->runInTransaction(function () use ($talent, $data): Talent {
            $talent->fill(Arr::only($data, [
                'display_name', 'headline', 'bio', 'slug',
                'base_city', 'base_country', 'booking_type', 'booking_value',
            ]));
            $talent->save();

            return $talent;
        }, ['talent_id' => $talent->id]);
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

            return $talent->refresh();
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
     * Publish the profile (Draft/Unpublished → Live; guarded by ToLive).
     */
    public function publish(Talent $talent): Talent
    {
        return $this->runInTransaction(function () use ($talent): Talent {
            $talent->status->transitionTo(Live::class);

            return $talent->refresh();
        }, ['talent_id' => $talent->id]);
    }

    /**
     * Take a live profile back down (Live → Unpublished).
     */
    public function unpublish(Talent $talent): Talent
    {
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
}
