<?php

namespace App\Feed\Links\Providers;

use App\Data\Feed\LinkPreviewData;

/**
 * Instagram URLs: profiles /{handle}, posts /p/{code}, reels /reel/{code},
 * stories /stories/{handle}/{id}. Instagram blocks scraping — this parse IS
 * the chip (ADR-F4).
 */
class InstagramProvider extends AbstractProvider
{
    private const RESERVED = ['explore', 'accounts', 'about', 'developer', 'directory', 'legal'];

    public function key(): string
    {
        return 'instagram';
    }

    protected function hosts(): array
    {
        return ['instagram.com'];
    }

    public function parse(string $url): LinkPreviewData
    {
        $segments = $this->segments($url);

        return match (true) {
            $segments === [] => $this->data(),
            in_array($segments[0], ['p', 'reel', 'reels', 'tv'], true) => $this->data(resourceId: $segments[1] ?? null),
            $segments[0] === 'stories' => $this->data(handle: $segments[1] ?? null, resourceId: $segments[2] ?? null),
            in_array($segments[0], self::RESERVED, true) => $this->data(),
            default => $this->data(handle: $segments[0]),
        };
    }
}
