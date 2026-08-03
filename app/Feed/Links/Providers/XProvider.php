<?php

namespace App\Feed\Links\Providers;

use App\Data\Feed\LinkPreviewData;

/**
 * X / Twitter URLs: profiles /{handle}, posts /{handle}/status/{id}.
 */
class XProvider extends AbstractProvider
{
    private const RESERVED = ['i', 'home', 'search', 'explore', 'hashtag', 'settings', 'intent'];

    public function key(): string
    {
        return 'x';
    }

    protected function hosts(): array
    {
        return ['x.com', 'twitter.com'];
    }

    public function parse(string $url): LinkPreviewData
    {
        $segments = $this->segments($url);

        return match (true) {
            $segments === [] || in_array($segments[0], self::RESERVED, true) => $this->data(),
            ($segments[1] ?? null) === 'status' => $this->data(handle: $segments[0], resourceId: $segments[2] ?? null),
            default => $this->data(handle: $segments[0]),
        };
    }
}
