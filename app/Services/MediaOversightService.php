<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Admin media-processing oversight (Phase 3A). Surface media whose conversions
 * haven't been generated and re-queue them. Gated on `manage-settings`;
 * transactional + activity-logged.
 */
class MediaOversightService extends AdminService
{
    /**
     * Media still awaiting (or missing) their derived conversions.
     */
    public function pendingConversions(int $perPage = 25): LengthAwarePaginator
    {
        return Media::query()
            ->whereRaw("(generated_conversions is null or generated_conversions = '[]' or generated_conversions = '{}')")
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Re-run the conversion pipeline for a single media item.
     */
    public function retry(User $admin, Media $media): void
    {
        $this->authorizePermission($admin, 'manage-settings');

        $this->runInTransaction(function () use ($admin, $media): void {
            Artisan::call('media-library:regenerate', ['--ids' => [(string) $media->getKey()], '--force' => true]);
            $this->record($admin, $media, 'media', 'media.conversions_retried', ['media_id' => $media->getKey()]);
        }, ['media_id' => $media->getKey()]);
    }
}
