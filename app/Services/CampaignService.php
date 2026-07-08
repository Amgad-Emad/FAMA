<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Campaign;
use App\Models\CampaignMedia;
use App\States\Campaign\CampaignState;
use App\States\Campaign\Cancelled;
use App\States\Campaign\Completed;
use App\States\Campaign\InProgress;
use App\States\Campaign\Open;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Campaign lifecycle + composition (brand-spec workflows 6–7). Create/edit,
 * attach roles + media, and drive the status transitions. Deals run under a
 * campaign via `deals.campaign_id`; a completed public campaign is a showcase
 * (Campaign::showcase()). Transactional, fail-logged to the `brands` channel.
 */
class CampaignService extends Service
{
    protected string $logChannel = 'brands';

    private const EDITABLE = [
        'title', 'type', 'description', 'budget_min', 'budget_max', 'currency',
        'location_city', 'location_country', 'start_date', 'end_date', 'is_public', 'positions_count',
    ];

    /**
     * @param  array<string, mixed>  $data  (+ optional `roles` => [talent_type_id => quantity])
     */
    public function create(Brand $brand, array $data): Campaign
    {
        return $this->runInTransaction(function () use ($brand, $data): Campaign {
            $campaign = $brand->campaigns()->create(Arr::only($data, self::EDITABLE) + [
                'title' => $data['title'],
                'slug' => $data['slug'] ?? Str::slug($data['title']).'-'.Str::lower(Str::random(6)),
                'status' => 'draft',
            ]);

            if (! empty($data['roles'])) {
                $this->syncRoles($campaign, $data['roles']);
            }

            return $campaign;
        }, ['brand_id' => $brand->getKey()]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Campaign $campaign, array $data): Campaign
    {
        return $this->runInTransaction(function () use ($campaign, $data): Campaign {
            $campaign->update(Arr::only($data, self::EDITABLE));

            if (array_key_exists('roles', $data)) {
                $this->syncRoles($campaign, $data['roles']);
            }

            return $campaign->refresh();
        }, ['campaign_id' => $campaign->getKey()]);
    }

    /**
     * @param  array<int, int>  $roles  talent_type_id => quantity
     */
    public function syncRoles(Campaign $campaign, array $roles): void
    {
        $campaign->talentTypes()->sync(
            collect($roles)->mapWithKeys(fn ($quantity, $typeId) => [$typeId => ['quantity' => max(1, (int) $quantity)]])->all(),
        );
    }

    public function addMedia(Campaign $campaign, UploadedFile $file, ?array $caption = null): CampaignMedia
    {
        return $this->runInTransaction(function () use ($campaign, $file, $caption): CampaignMedia {
            $item = $campaign->gallery()->create([
                'media_type' => 'image',
                'caption' => $caption,
                'position' => $campaign->gallery()->count(),
            ]);
            $item->addMedia($file)->toMediaCollection('media');

            return $item;
        }, ['campaign_id' => $campaign->getKey()]);
    }

    public function open(Campaign $campaign): Campaign
    {
        return $this->transition($campaign, Open::class);
    }

    public function start(Campaign $campaign): Campaign
    {
        return $this->transition($campaign, InProgress::class);
    }

    public function complete(Campaign $campaign): Campaign
    {
        return $this->transition($campaign, Completed::class);
    }

    public function cancel(Campaign $campaign): Campaign
    {
        return $this->transition($campaign, Cancelled::class);
    }

    /**
     * Toggle listed ⇄ private without changing status.
     */
    public function setPublic(Campaign $campaign, bool $public): Campaign
    {
        return $this->runInTransaction(function () use ($campaign, $public): Campaign {
            $campaign->update(['is_public' => $public]);

            return $campaign->refresh();
        }, ['campaign_id' => $campaign->getKey()]);
    }

    /**
     * @param  class-string<CampaignState>  $state
     */
    private function transition(Campaign $campaign, string $state): Campaign
    {
        return $this->runInTransaction(function () use ($campaign, $state): Campaign {
            $campaign->status->transitionTo($state);

            return $campaign->refresh();
        }, ['campaign_id' => $campaign->getKey()]);
    }
}
