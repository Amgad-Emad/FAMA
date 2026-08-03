<?php

namespace App\Feed\Links\Providers;

use App\Data\Feed\LinkPreviewData;

/**
 * Vimeo URLs: videos /{numeric-id}, channels /channels/{name}, else
 * profiles /{handle}.
 */
class VimeoProvider extends AbstractProvider
{
    public function key(): string
    {
        return 'vimeo';
    }

    protected function hosts(): array
    {
        return ['vimeo.com'];
    }

    public function parse(string $url): LinkPreviewData
    {
        $segments = $this->segments($url);

        return match (true) {
            $segments === [] => $this->data(),
            ctype_digit($segments[0]) => $this->data(resourceId: $segments[0]),
            $segments[0] === 'channels' => $this->data(handle: $segments[1] ?? null),
            default => $this->data(handle: $segments[0]),
        };
    }
}
