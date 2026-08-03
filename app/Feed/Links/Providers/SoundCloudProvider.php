<?php

namespace App\Feed\Links\Providers;

use App\Data\Feed\LinkPreviewData;

/**
 * SoundCloud URLs: profiles /{handle}, tracks /{handle}/{track-slug}.
 */
class SoundCloudProvider extends AbstractProvider
{
    private const RESERVED = ['discover', 'search', 'stream', 'upload', 'you'];

    public function key(): string
    {
        return 'soundcloud';
    }

    protected function hosts(): array
    {
        return ['soundcloud.com'];
    }

    public function parse(string $url): LinkPreviewData
    {
        $segments = $this->segments($url);

        return match (true) {
            $segments === [] || in_array($segments[0], self::RESERVED, true) => $this->data(),
            isset($segments[1]) => $this->data(handle: $segments[0], resourceId: $segments[1]),
            default => $this->data(handle: $segments[0]),
        };
    }
}
