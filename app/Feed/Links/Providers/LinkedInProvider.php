<?php

namespace App\Feed\Links\Providers;

use App\Data\Feed\LinkPreviewData;

/**
 * LinkedIn URLs: people /in/{handle}, companies /company/{handle},
 * posts /posts/{id}.
 */
class LinkedInProvider extends AbstractProvider
{
    public function key(): string
    {
        return 'linkedin';
    }

    protected function hosts(): array
    {
        return ['linkedin.com'];
    }

    public function parse(string $url): LinkPreviewData
    {
        $segments = $this->segments($url);

        return match ($segments[0] ?? null) {
            'in', 'company', 'school', 'showcase' => $this->data(handle: $segments[1] ?? null),
            'posts', 'pulse' => $this->data(resourceId: $segments[1] ?? null),
            default => $this->data(),
        };
    }
}
