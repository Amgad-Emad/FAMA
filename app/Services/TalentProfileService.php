<?php

namespace App\Services;

use App\Models\AgencyAffiliation;
use App\Models\PressFeature;
use App\Models\Review;
use App\Models\Service as ServiceModel;
use App\Models\Talent;
use App\States\Affiliation\Past;
use App\States\Availability\Available;
use App\States\Availability\Booked;
use App\States\Availability\Unavailable;
use App\States\Review\Approved;
use App\States\Review\Rejected;
use App\States\ServiceStatus\Active;
use App\States\ServiceStatus\Paused;
use App\States\TalentProfile\Live;
use App\States\TalentProfile\Unpublished;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use InvalidArgumentException;

/**
 * Orchestrates talent profile management (talent-spec workflows #10, #16–#20):
 * core identity fields, availability, publish/unpublish, the rate card,
 * reviews moderation, and affiliations/press. State changes go through the
 * relevant state machine; every multi-write op is transactional + fail-logged.
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
                'willing_to_travel', 'travel_regions', 'rate_tier',
            ]));
            $talent->save();

            return $talent;
        }, ['talent_id' => $talent->id]);
    }

    /**
     * Replace the hero image (uploaded → media library, single file).
     */
    public function setHeroImage(Talent $talent, UploadedFile $file): Talent
    {
        return $this->runInTransaction(function () use ($talent, $file): Talent {
            $talent->addMedia($file)->toMediaCollection('hero');

            return $talent;
        }, ['talent_id' => $talent->id], 'media');
    }

    /**
     * Move availability (available ⇄ booked ⇄ unavailable). Idempotent.
     */
    public function setAvailability(Talent $talent, string $status): Talent
    {
        return $this->runInTransaction(function () use ($talent, $status): Talent {
            $target = match ($status) {
                'available' => Available::class,
                'booked' => Booked::class,
                'unavailable' => Unavailable::class,
                default => throw new InvalidArgumentException("Unknown availability [{$status}]."),
            };

            if ($talent->availability_status->canTransitionTo($target)) {
                $talent->availability_status->transitionTo($target);
            }

            return $talent->refresh();
        }, ['talent_id' => $talent->id]);
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

    // ----- Rate card ---------------------------------------------------------

    /**
     * @param  array<string, mixed>  $data
     */
    public function addService(Talent $talent, array $data): ServiceModel
    {
        return $this->runInTransaction(
            fn (): ServiceModel => $talent->services()->create(Arr::only($data, [
                'name', 'description', 'price', 'currency', 'price_unit', 'duration_minutes', 'position',
            ])),
            ['talent_id' => $talent->id],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateService(ServiceModel $service, array $data): ServiceModel
    {
        return $this->runInTransaction(function () use ($service, $data): ServiceModel {
            $service->fill(Arr::only($data, [
                'name', 'description', 'price', 'currency', 'price_unit', 'duration_minutes', 'position',
            ]));
            $service->save();

            return $service;
        }, ['service_id' => $service->id]);
    }

    /**
     * Pause a service (Active → Paused). Idempotent.
     */
    public function pauseService(ServiceModel $service): ServiceModel
    {
        return $this->transitionIfPossible($service, 'status', Paused::class, ['service_id' => $service->id]);
    }

    /**
     * Reactivate a service (Paused → Active). Idempotent.
     */
    public function activateService(ServiceModel $service): ServiceModel
    {
        return $this->transitionIfPossible($service, 'status', Active::class, ['service_id' => $service->id]);
    }

    public function removeService(ServiceModel $service): void
    {
        $this->runInTransaction(fn () => $service->delete(), ['service_id' => $service->id]);
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

    // ----- Affiliations & press ---------------------------------------------

    /**
     * @param  array<string, mixed>  $data
     */
    public function addAffiliation(Talent $talent, array $data): AgencyAffiliation
    {
        return $this->runInTransaction(
            fn (): AgencyAffiliation => $talent->agencyAffiliations()->create(Arr::only($data, [
                'agency_name', 'agency_url', 'representation_type', 'region',
            ])),
            ['talent_id' => $talent->id],
        );
    }

    /**
     * Mark an affiliation as past (Current → Past).
     */
    public function endAffiliation(AgencyAffiliation $affiliation): AgencyAffiliation
    {
        return $this->transitionIfPossible($affiliation, 'status', Past::class, ['affiliation_id' => $affiliation->id]);
    }

    public function removeAffiliation(AgencyAffiliation $affiliation): void
    {
        $this->runInTransaction(fn () => $affiliation->delete(), ['affiliation_id' => $affiliation->id]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addPressFeature(Talent $talent, array $data): PressFeature
    {
        return $this->runInTransaction(
            fn (): PressFeature => $talent->pressFeatures()->create(Arr::only($data, [
                'publication', 'title', 'url', 'published_date', 'position',
            ])),
            ['talent_id' => $talent->id],
        );
    }

    public function removePressFeature(PressFeature $feature): void
    {
        $this->runInTransaction(fn () => $feature->delete(), ['press_feature_id' => $feature->id]);
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
