<?php

namespace App\Data\Feed;

use App\Data\BaseData;

/**
 * One http/https URL found in a raw body (ADR-F4), with its multibyte-correct
 * character offset + length into the raw text.
 */
class UrlData extends BaseData
{
    public function __construct(
        public string $url,
        public int $startOffset,
        public int $length,
    ) {
    }
}
