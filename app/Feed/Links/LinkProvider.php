<?php

namespace App\Feed\Links;

use App\Data\Feed\LinkPreviewData;

/**
 * A link-chip driver (feed-spec §8, ADR-F4). parse() MUST work from the URL
 * string alone with zero network calls — the chip renders instantly, and
 * platforms like Instagram block scraping, so the parse is the product, not
 * a fallback.
 */
interface LinkProvider
{
    /**
     * The stable driver key stored in link_previews.provider.
     */
    public function key(): string;

    /**
     * Whether this driver understands the URL.
     */
    public function matches(string $url): bool;

    /**
     * Extract provider/handle/resource from the URL string. Never touches the
     * network; never throws — an unreadable path yields a bare provider chip.
     */
    public function parse(string $url): LinkPreviewData;
}
