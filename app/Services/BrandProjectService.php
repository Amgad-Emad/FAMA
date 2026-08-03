<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\BrandProject;
use App\Models\BrandProjectMedia;
use App\States\BrandProject\BrandProjectState;
use App\States\BrandProject\Cancelled;
use App\States\BrandProject\Completed;
use App\States\BrandProject\InProgress;
use App\States\BrandProject\Open;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Project lifecycle + composition (brand-spec workflows 6–7). Create/edit,
 * attach roles + media, and drive the status transitions. Contracts run under a
 * project via `contracts.brand_project_id`; a completed public project is a showcase
 * (BrandProject::showcase()). Transactional, fail-logged to the `brands` channel.
 */
class BrandProjectService extends Service
{
    protected string $logChannel = 'brands';

    private const EDITABLE = [
        'title', 'type', 'description', 'budget_min', 'budget_max', 'currency',
        'location_city', 'location_country', 'start_date', 'end_date', 'is_public',
        'talent_type_id', 'budget_is_public',
    ];

    /**
     * @param  array<string, mixed>  $data  (+ optional `roles` => [talent_type_id => quantity])
     */
    public function create(Brand $brand, array $data): BrandProject
    {
        return $this->runInTransaction(function () use ($brand, $data): BrandProject {
            $campaign = $brand->projects()->create(Arr::only($data, self::EDITABLE) + [
                'title' => $data['title'],
                'slug' => $data['slug'] ?? Str::slug($data['title']).'-'.Str::lower(Str::random(6)),
                'status' => 'draft',
            ]);

            return $campaign;
        }, ['brand_id' => $brand->getKey()]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(BrandProject $campaign, array $data): BrandProject
    {
        return $this->runInTransaction(function () use ($campaign, $data): BrandProject {
            $campaign->update(Arr::only($data, self::EDITABLE));

            return $campaign->refresh();
        }, ['brand_project_id' => $campaign->getKey()]);
    }

    public function addMedia(BrandProject $campaign, UploadedFile $file, ?array $caption = null): BrandProjectMedia
    {
        return $this->runInTransaction(function () use ($campaign, $file, $caption): BrandProjectMedia {
            $item = $campaign->gallery()->create([
                'media_type' => 'image',
                'caption' => $caption,
                'position' => $campaign->gallery()->count(),
            ]);
            $item->addMedia($file)->toMediaCollection('media');

            return $item;
        }, ['brand_project_id' => $campaign->getKey()]);
    }

    public function open(BrandProject $campaign): BrandProject
    {
        $project = $this->transition($campaign, Open::class);

        $this->notifySubscribers($project);

        return $project;
    }

    /**
     * When a public opportunity opens, notify every talent who subscribed to
     * this brand via the notify-bell (the talent→brand follow edge). Private
     * projects are never broadcast. One query for the subscriber set; the
     * notification carries a localizable key + the deep link to apply.
     */
    private function notifySubscribers(BrandProject $project): void
    {
        if (! $project->is_public) {
            return;
        }

        $brand = $project->brand()->first();

        if ($brand === null) {
            return;
        }

        $subscriberIds = \App\Models\Follow::query()
            ->where('followable_type', 'brand')
            ->where('followable_id', $brand->id)
            ->where('follower_type', 'talent')
            ->pluck('follower_id');

        if ($subscriberIds->isEmpty()) {
            return;
        }

        $payload = [
            'type' => 'brand_opportunity',
            'message' => ':brand posted a new opportunity: :title',
            'params' => [
                'brand' => $brand->name,
                'title' => $project->title,
            ],
            'url' => route('brand.project.public', ['brand' => $brand->slug, 'project' => $project->slug]),
            'actor' => [
                'type' => 'brand',
                'id' => $brand->id,
                'name' => $brand->name,
                'username' => $brand->slug,
                'avatar_url' => $brand->logo_url,
            ],
        ];

        \App\Models\Talent::query()->whereIn('id', $subscriberIds)->get()
            ->each(fn (\App\Models\Talent $talent) => $talent->notify(new \App\Notifications\Feed\BrandOpportunityPosted($payload)));
    }

    public function start(BrandProject $campaign): BrandProject
    {
        return $this->transition($campaign, InProgress::class);
    }

    public function complete(BrandProject $campaign): BrandProject
    {
        return $this->transition($campaign, Completed::class);
    }

    public function cancel(BrandProject $campaign): BrandProject
    {
        return $this->transition($campaign, Cancelled::class);
    }

    /**
     * Toggle listed ⇄ private without changing status.
     */
    public function setPublic(BrandProject $campaign, bool $public): BrandProject
    {
        return $this->runInTransaction(function () use ($campaign, $public): BrandProject {
            $campaign->update(['is_public' => $public]);

            return $campaign->refresh();
        }, ['brand_project_id' => $campaign->getKey()]);
    }

    /**
     * @param  class-string<BrandProjectState>  $state
     */
    private function transition(BrandProject $campaign, string $state): BrandProject
    {
        return $this->runInTransaction(function () use ($campaign, $state): BrandProject {
            $campaign->status->transitionTo($state);

            return $campaign->refresh();
        }, ['brand_project_id' => $campaign->getKey()]);
    }
}
