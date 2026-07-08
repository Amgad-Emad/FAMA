<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\BrandAesthetic;
use App\Models\BrandCreativeNeed;
use App\States\Brand\Complete;
use App\States\Brand\Onboarding;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

/**
 * The 6-step brand onboarding wizard (brand-spec workflow 1). Each step is a
 * transactional, idempotent write with fail-logging to the `brands` channel;
 * the first step moves registered → onboarding, the last flips is_complete
 * (onboarding → complete, which syncs the flag). The "first feed" payoff (step 6)
 * is the discovery feed query — see App\Queries\BrandTalentFeed.
 */
class BrandOnboardingService extends Service
{
    protected string $logChannel = 'brands';

    /**
     * Step 1 — identity + persona.
     *
     * @param  array<string, mixed>  $data
     */
    public function identity(Brand $brand, array $data): Brand
    {
        return $this->runInTransaction(function () use ($brand, $data): Brand {
            $brand->fill(Arr::only($data, ['name', 'description', 'industry', 'brand_stage']))->save();

            if ($brand->status->getValue() === 'registered') {
                $brand->status->transitionTo(Onboarding::class);
            }

            return $brand->refresh();
        }, ['brand_id' => $brand->getKey(), 'step' => 'identity']);
    }

    /**
     * Step 2 — location + reach.
     *
     * @param  array<string, mixed>  $data
     */
    public function location(Brand $brand, array $data): Brand
    {
        return $this->runInTransaction(function () use ($brand, $data): Brand {
            $brand->fill(Arr::only($data, ['base_city', 'base_country', 'geographic_reach']))->save();

            return $brand->refresh();
        }, ['brand_id' => $brand->getKey(), 'step' => 'location']);
    }

    /**
     * Step 3 — creative needs (talent types + project types promoted to pivots).
     *
     * @param  array{talent_type_ids?: list<int>, project_types?: list<string>, project_frequency?: string}  $data
     */
    public function creativeNeeds(Brand $brand, array $data): BrandCreativeNeed
    {
        return $this->runInTransaction(function () use ($brand, $data): BrandCreativeNeed {
            $need = $brand->creativeNeed()->updateOrCreate([], Arr::only($data, ['project_frequency']));

            if (array_key_exists('talent_type_ids', $data)) {
                $need->talentTypes()->sync($data['talent_type_ids']);
            }

            if (array_key_exists('project_types', $data)) {
                $need->projectTypes()->delete();
                foreach (array_unique($data['project_types']) as $projectType) {
                    $need->projectTypes()->create(['project_type' => $projectType]);
                }
            }

            return $need;
        }, ['brand_id' => $brand->getKey(), 'step' => 'creative_needs']);
    }

    /**
     * Step 4 — aesthetic (mood tags promoted to a pivot) + uploaded images.
     *
     * @param  array{mood_tags?: list<string>, brand_references?: string}  $data
     * @param  list<UploadedFile>  $images
     */
    public function aesthetic(Brand $brand, array $data, array $images = []): BrandAesthetic
    {
        return $this->runInTransaction(function () use ($brand, $data, $images): BrandAesthetic {
            $aesthetic = $brand->aesthetic()->updateOrCreate([], Arr::only($data, ['brand_references']));

            if (array_key_exists('mood_tags', $data)) {
                $aesthetic->moodTags()->delete();
                foreach (array_unique($data['mood_tags']) as $tag) {
                    $aesthetic->moodTags()->create(['tag' => $tag]);
                }
            }

            $base = $brand->images()->count();
            foreach (array_values($images) as $i => $file) {
                $image = $brand->images()->create(['position' => $base + $i]);
                $image->addMedia($file)->toMediaCollection('image');
            }

            return $aesthetic;
        }, ['brand_id' => $brand->getKey(), 'step' => 'aesthetic']);
    }

    /**
     * Step 5 — budget tier.
     */
    public function budget(Brand $brand, string $tier): BrandCreativeNeed
    {
        return $this->runInTransaction(
            fn () => $brand->creativeNeed()->updateOrCreate([], ['budget_tier' => $tier]),
            ['brand_id' => $brand->getKey(), 'step' => 'budget'],
        );
    }

    /**
     * Step 6 — finish: flip is_complete (onboarding → complete). The first feed
     * is then rendered from BrandTalentFeed.
     */
    public function complete(Brand $brand): Brand
    {
        return $this->runInTransaction(function () use ($brand): Brand {
            if ($brand->status->getValue() === 'onboarding') {
                $brand->status->transitionTo(Complete::class);
            }

            return $brand->refresh();
        }, ['brand_id' => $brand->getKey(), 'step' => 'complete']);
    }
}
