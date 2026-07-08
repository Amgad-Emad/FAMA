<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\User;
use App\States\Campaign\Cancelled;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Admin campaign oversight (Phase 3A). Filter campaigns by status, and intervene
 * (cancel / force-private). Gated on `moderate-content`; interventions are
 * transactional + activity-logged with the admin as causer.
 */
class CampaignOversightService extends AdminService
{
    /**
     * Paginated campaign list for oversight, optionally filtered by status.
     */
    public function forStatus(User $admin, ?string $status = null, int $perPage = 20): LengthAwarePaginator
    {
        $this->authorizeAdmin($admin, 'oversee', Campaign::class);

        return Campaign::query()
            ->with(['brand', 'media'])
            ->withCount('deals')
            ->when($status !== null, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate($perPage);
    }

    public function cancel(User $admin, Campaign $campaign, ?string $reason = null): Campaign
    {
        $this->authorizeAdmin($admin, 'oversee', $campaign);

        return $this->runInTransaction(function () use ($admin, $campaign, $reason): Campaign {
            if ($campaign->status->canTransitionTo(Cancelled::class)) {
                $campaign->status->transitionTo(Cancelled::class);
            }
            $this->record($admin, $campaign, 'moderation', 'campaign.cancelled_by_admin', ['reason' => $reason]);

            return $campaign->refresh();
        }, ['campaign_id' => $campaign->getKey()]);
    }

    public function forcePrivate(User $admin, Campaign $campaign, ?string $reason = null): Campaign
    {
        $this->authorizeAdmin($admin, 'oversee', $campaign);

        return $this->runInTransaction(function () use ($admin, $campaign, $reason): Campaign {
            $campaign->update(['is_public' => false]);
            $this->record($admin, $campaign, 'moderation', 'campaign.forced_private', ['reason' => $reason]);

            return $campaign->refresh();
        }, ['campaign_id' => $campaign->getKey()]);
    }
}
