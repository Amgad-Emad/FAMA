<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

/**
 * On upload, medialibrary automatically queues the registered conversions
 * (thumbnails). This listener records that on the dedicated media log channel so
 * the pipeline is observable (talent-spec: "media uploaded → queue conversions").
 */
class LogMediaUploaded
{
    public function handle(MediaHasBeenAddedEvent $event): void
    {
        $media = $event->media;

        Log::channel('media')->info('Media uploaded; conversions queued.', [
            'media_id' => $media->getKey(),
            'collection' => $media->collection_name,
            'model_type' => $media->model_type,
            'model_id' => $media->model_id,
        ]);
    }
}
