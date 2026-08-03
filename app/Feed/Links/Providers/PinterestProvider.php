<?php

namespace App\Feed\Links\Providers;

use App\Data\Feed\LinkPreviewData;

/**
 * Pinterest URLs: pins /pin/{id}, else profiles /{handle}.
 */
class PinterestProvider extends AbstractProvider
{
    private const RESERVED = ['pin', 'search', 'ideas', 'today', 'settings'];

    public function key(): string
    {
        return 'pinterest';
    }

    protected function hosts(): array
    {
        return ['pinterest.com'];
    }

    public function parse(string $url): LinkPreviewData
    {
        $segments = $this->segments($url);

        return match (true) {
            $segments === [] => $this->data(),
            $segments[0] === 'pin' => $this->data(resourceId: $segments[1] ?? null),
            in_array($segments[0], self::RESERVED, true) => $this->data(),
            default => $this->data(handle: $segments[0]),
        };
    }
}
