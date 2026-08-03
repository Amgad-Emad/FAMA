<?php

namespace App\Feed\Links\Providers;

use App\Data\Feed\LinkPreviewData;

/**
 * Snapchat URLs: profiles /add/{handle} and /@{handle}.
 */
class SnapchatProvider extends AbstractProvider
{
    public function key(): string
    {
        return 'snapchat';
    }

    protected function hosts(): array
    {
        return ['snapchat.com'];
    }

    public function parse(string $url): LinkPreviewData
    {
        $segments = $this->segments($url);

        return match (true) {
            $segments === [] => $this->data(),
            $segments[0] === 'add' => $this->data(handle: $segments[1] ?? null),
            str_starts_with($segments[0], '@') => $this->data(handle: $segments[0]),
            default => $this->data(),
        };
    }
}
