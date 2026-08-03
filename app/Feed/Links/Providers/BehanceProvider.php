<?php

namespace App\Feed\Links\Providers;

use App\Data\Feed\LinkPreviewData;

/**
 * Behance URLs: profiles /{handle}, projects /gallery/{id}/{slug}.
 */
class BehanceProvider extends AbstractProvider
{
    private const RESERVED = ['gallery', 'galleries', 'search', 'joblist', 'assets', 'live'];

    public function key(): string
    {
        return 'behance';
    }

    protected function hosts(): array
    {
        return ['behance.net'];
    }

    public function parse(string $url): LinkPreviewData
    {
        $segments = $this->segments($url);

        return match (true) {
            $segments === [] => $this->data(),
            $segments[0] === 'gallery' => $this->data(resourceId: $segments[1] ?? null),
            in_array($segments[0], self::RESERVED, true) => $this->data(),
            default => $this->data(handle: $segments[0]),
        };
    }
}
