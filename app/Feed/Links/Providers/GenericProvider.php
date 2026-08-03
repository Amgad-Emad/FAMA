<?php

namespace App\Feed\Links\Providers;

use App\Data\Feed\LinkPreviewData;

/**
 * The fallback driver — matches everything, claims nothing. The chip labels
 * itself with the registrable domain (renderer-side); enrichment may fill a
 * title later.
 */
class GenericProvider extends AbstractProvider
{
    public function key(): string
    {
        return 'generic';
    }

    protected function hosts(): array
    {
        return [];
    }

    public function matches(string $url): bool
    {
        return true;
    }

    public function parse(string $url): LinkPreviewData
    {
        return $this->data();
    }
}
