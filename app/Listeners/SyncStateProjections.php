<?php

namespace App\Listeners;

use App\Models\AgencyAffiliation;
use App\Models\Brand;
use App\Models\BrandReview;
use App\Models\DealFlow;
use App\Models\ProfileBlock;
use App\Models\Review;
use App\Models\Service;
use App\Models\Talent;
use Spatie\ModelStates\Events\StateChanged;

/**
 * Keeps the denormalised boolean/timestamp projections in sync with the state
 * machines (the state is authoritative; the booleans are conveniences kept for
 * Phase 1A queries/views). Runs synchronously on every StateChanged so the two
 * representations never drift. Uses saveQuietly() to avoid re-triggering events.
 */
class SyncStateProjections
{
    public function handle(StateChanged $event): void
    {
        $model = $event->model;
        $final = $event->finalState?->getValue();

        match (true) {
            $model instanceof Talent && $event->field === 'status' => $this->syncTalent($model, $final),
            $model instanceof Brand && $event->field === 'status' => $this->syncBrand($model, $final),
            $model instanceof DealFlow => $model->forceFill(['is_active' => $final === 'active'])->saveQuietly(),
            $model instanceof BrandReview => $model->forceFill(['is_approved' => $final === 'approved'])->saveQuietly(),
            $model instanceof ProfileBlock => $model->forceFill(['is_visible' => $final === 'visible'])->saveQuietly(),
            $model instanceof Review => $model->forceFill(['is_approved' => $final === 'approved'])->saveQuietly(),
            $model instanceof Service => $model->forceFill(['is_active' => $final === 'active'])->saveQuietly(),
            $model instanceof AgencyAffiliation => $model->forceFill(['is_current' => $final === 'current'])->saveQuietly(),
            default => null,
        };
    }

    /**
     * Brand flags projected from the lifecycle status. `is_verified` is
     * orthogonal (a one-way admin flag) and is NOT touched here.
     */
    private function syncBrand(Brand $brand, ?string $final): void
    {
        $brand->forceFill([
            'is_complete' => in_array($final, ['complete', 'published', 'unpublished', 'suspended'], true),
            'is_published' => $final === 'published',
            'is_active' => $final !== 'suspended',
        ])->saveQuietly();
    }

    /**
     * Publishing side effect: is_published + a one-time published_at stamp.
     */
    private function syncTalent(Talent $talent, ?string $final): void
    {
        $isLive = $final === 'live';

        $attributes = ['is_published' => $isLive];

        if ($isLive && $talent->published_at === null) {
            $attributes['published_at'] = now();
        }

        $talent->forceFill($attributes)->saveQuietly();
    }
}
