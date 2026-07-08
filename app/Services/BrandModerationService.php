<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\User;
use App\States\Brand\Suspended;
use App\States\Brand\Unpublished;

/**
 * Admin moderation of brands (Phase 3A). Verify (one-way), suspend, unpublish,
 * soft-delete (+ restore). Gated on `moderate-content` (BrandPolicy@moderate);
 * transactional + activity-logged with the admin as causer.
 */
class BrandModerationService extends AdminService
{
    /**
     * Verify a brand — a one-way trust flag, orthogonal to the lifecycle status.
     */
    public function verify(User $admin, Brand $brand): Brand
    {
        $this->authorizeAdmin($admin, 'moderate', $brand);

        return $this->runInTransaction(function () use ($admin, $brand): Brand {
            $brand->update(['is_verified' => true]);
            $this->record($admin, $brand, 'moderation', 'brand.verified');

            return $brand->refresh();
        }, ['brand_id' => $brand->getKey()]);
    }

    public function suspend(User $admin, Brand $brand, ?string $reason = null): Brand
    {
        $this->authorizeAdmin($admin, 'moderate', $brand);

        return $this->runInTransaction(function () use ($admin, $brand, $reason): Brand {
            if ($brand->status->canTransitionTo(Suspended::class)) {
                $brand->status->transitionTo(Suspended::class);
            }
            $this->record($admin, $brand, 'moderation', 'brand.suspended', ['reason' => $reason]);

            return $brand->refresh();
        }, ['brand_id' => $brand->getKey()]);
    }

    public function unpublish(User $admin, Brand $brand, ?string $reason = null): Brand
    {
        $this->authorizeAdmin($admin, 'moderate', $brand);

        return $this->runInTransaction(function () use ($admin, $brand, $reason): Brand {
            if ($brand->status->canTransitionTo(Unpublished::class)) {
                $brand->status->transitionTo(Unpublished::class);
            }
            $this->record($admin, $brand, 'moderation', 'brand.unpublished', ['reason' => $reason]);

            return $brand->refresh();
        }, ['brand_id' => $brand->getKey()]);
    }

    public function softDelete(User $admin, Brand $brand, ?string $reason = null): void
    {
        $this->authorizeAdmin($admin, 'moderate', $brand);

        $this->runInTransaction(function () use ($admin, $brand, $reason): void {
            $this->record($admin, $brand, 'moderation', 'brand.soft_deleted', ['reason' => $reason]);
            $brand->delete();
        }, ['brand_id' => $brand->getKey()]);
    }
}
