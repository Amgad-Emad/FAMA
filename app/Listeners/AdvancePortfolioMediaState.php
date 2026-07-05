<?php

namespace App\Listeners;

use App\Models\PortfolioItem;
use App\States\PortfolioMedia\Processed;
use Spatie\MediaLibrary\Conversions\Events\ConversionHasBeenCompletedEvent;

/**
 * Advances a portfolio item's media lifecycle uploaded → processed once its
 * thumbnail conversion has been generated (talent-spec media lifecycle).
 */
class AdvancePortfolioMediaState
{
    public function handle(ConversionHasBeenCompletedEvent $event): void
    {
        $model = $event->media->model;

        if ($model instanceof PortfolioItem && $model->status->canTransitionTo(Processed::class)) {
            $model->status->transitionTo(Processed::class);
        }
    }
}
