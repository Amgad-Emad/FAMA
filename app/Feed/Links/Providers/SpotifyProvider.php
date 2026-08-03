<?php

namespace App\Feed\Links\Providers;

use App\Data\Feed\LinkPreviewData;

/**
 * Spotify URLs: /{type}/{id} for track / album / artist / playlist /
 * episode / show, canonicalized onto open.spotify.com.
 */
class SpotifyProvider extends AbstractProvider
{
    private const TYPES = ['track', 'album', 'artist', 'playlist', 'episode', 'show'];

    public function key(): string
    {
        return 'spotify';
    }

    protected function hosts(): array
    {
        return ['spotify.com'];
    }

    public function parse(string $url): LinkPreviewData
    {
        $segments = $this->segments($url);

        if (count($segments) >= 2 && in_array($segments[0], self::TYPES, true)) {
            return $this->data(
                resourceId: $segments[1],
                canonicalUrl: 'https://open.spotify.com/'.$segments[0].'/'.$segments[1],
            );
        }

        return $this->data(handle: $segments[0] === 'user' ? ($segments[1] ?? null) : null);
    }
}
