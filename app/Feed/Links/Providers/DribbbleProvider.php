<?php

namespace App\Feed\Links\Providers;

use App\Data\Feed\LinkPreviewData;

/**
 * Dribbble URLs: shots /shots/{id-slug}, else profiles /{handle}.
 */
class DribbbleProvider extends AbstractProvider
{
    private const RESERVED = ['shots', 'search', 'jobs', 'designers', 'tags'];

    public function key(): string
    {
        return 'dribbble';
    }

    protected function hosts(): array
    {
        return ['dribbble.com'];
    }

    public function parse(string $url): LinkPreviewData
    {
        $segments = $this->segments($url);

        return match (true) {
            $segments === [] => $this->data(),
            $segments[0] === 'shots' => $this->data(resourceId: $segments[1] ?? null),
            in_array($segments[0], self::RESERVED, true) => $this->data(),
            default => $this->data(handle: $segments[0]),
        };
    }
}
