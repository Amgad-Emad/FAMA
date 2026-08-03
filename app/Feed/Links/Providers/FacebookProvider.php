<?php

namespace App\Feed\Links\Providers;

use App\Data\Feed\LinkPreviewData;

/**
 * Facebook URLs: pages/profiles /{handle}, numeric profiles
 * /profile.php?id={id}, videos /watch?v={id}, groups /groups/{handle}.
 */
class FacebookProvider extends AbstractProvider
{
    public function key(): string
    {
        return 'facebook';
    }

    protected function hosts(): array
    {
        return ['facebook.com', 'fb.com', 'fb.watch'];
    }

    public function parse(string $url): LinkPreviewData
    {
        $segments = $this->segments($url);

        return match (true) {
            $segments === [] => $this->data(),
            $segments[0] === 'profile.php' => $this->data(resourceId: $this->query($url, 'id')),
            $segments[0] === 'watch' => $this->data(resourceId: $this->query($url, 'v') ?? ($segments[1] ?? null)),
            in_array($segments[0], ['groups', 'pages', 'events'], true) => $this->data(handle: $segments[1] ?? null),
            default => $this->data(handle: $segments[0]),
        };
    }
}
