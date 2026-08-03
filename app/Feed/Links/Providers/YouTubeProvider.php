<?php

namespace App\Feed\Links\Providers;

use App\Data\Feed\LinkPreviewData;

/**
 * YouTube URLs: /watch?v={id}, youtu.be/{id}, channels /@{handle} (also
 * /channel|c|user/{handle}), /shorts/{id}, /embed/{id}.
 */
class YouTubeProvider extends AbstractProvider
{
    public function key(): string
    {
        return 'youtube';
    }

    protected function hosts(): array
    {
        return ['youtube.com', 'youtu.be'];
    }

    public function parse(string $url): LinkPreviewData
    {
        $segments = $this->segments($url);
        $host = $this->host($url);

        if ($host === 'youtu.be' || str_ends_with($host, '.youtu.be')) {
            return $this->data(resourceId: $segments[0] ?? null);
        }

        return match (true) {
            $segments === [] => $this->data(),
            $segments[0] === 'watch' => $this->data(resourceId: $this->query($url, 'v')),
            in_array($segments[0], ['shorts', 'embed', 'live'], true) => $this->data(resourceId: $segments[1] ?? null),
            str_starts_with($segments[0], '@') => $this->data(handle: $segments[0]),
            in_array($segments[0], ['channel', 'c', 'user'], true) => $this->data(handle: $segments[1] ?? null),
            default => $this->data(),
        };
    }
}
