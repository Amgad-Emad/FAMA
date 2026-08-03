<?php

namespace App\Feed\Links\Providers;

use App\Data\Feed\LinkPreviewData;

/**
 * TikTok URLs: profiles /@{handle}, videos /@{handle}/video/{id}, and
 * vm.tiktok.com short links (code only).
 */
class TikTokProvider extends AbstractProvider
{
    public function key(): string
    {
        return 'tiktok';
    }

    protected function hosts(): array
    {
        return ['tiktok.com'];
    }

    public function parse(string $url): LinkPreviewData
    {
        $segments = $this->segments($url);

        if ($this->host($url) === 'vm.tiktok.com') {
            return $this->data(resourceId: $segments[0] ?? null);
        }

        if ($segments !== [] && str_starts_with($segments[0], '@')) {
            $handle = $segments[0];
            $video = ($segments[1] ?? null) === 'video' ? ($segments[2] ?? null) : null;

            return $this->data(handle: $handle, resourceId: $video);
        }

        return $this->data();
    }
}
