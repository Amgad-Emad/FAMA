<?php

namespace App\Data\Feed;

use App\Data\BaseData;

/**
 * A LinkProvider driver's zero-network parse of a URL (ADR-F4): which
 * provider, and the handle / resource id / canonical URL readable from the
 * URL string alone. This is everything the instant chip needs; title /
 * description / image arrive later via FetchLinkPreviewJob.
 */
class LinkPreviewData extends BaseData
{
    public function __construct(
        public string $provider,
        public ?string $providerHandle = null,
        public ?string $providerResourceId = null,
        public ?string $canonicalUrl = null,
    ) {
    }
}
