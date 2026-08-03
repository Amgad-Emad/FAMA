<?php

namespace App\Data\Feed;

use App\Data\BaseData;
use App\Feed\Actor;

/**
 * One feed page request (ADR-F5): the viewer (null = guest), the session
 * seed the candidate list is keyed by, the numeric offset the opaque cursor
 * decoded to, and the page size.
 */
class FeedQueryData extends BaseData
{
    public function __construct(
        public ?Actor $viewer,
        public string $seed,
        public int $cursor = 0,
        public int $perPage = 20,
        public ?string $skill = null,
        public bool $followingOnly = false,
    ) {
    }
}
