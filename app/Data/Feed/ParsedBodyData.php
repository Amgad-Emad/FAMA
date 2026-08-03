<?php

namespace App\Data\Feed;

use App\Data\BaseData;
use Spatie\LaravelData\Attributes\DataCollectionOf;

/**
 * The full parse result for a post/comment body (feed-spec §7–§8): the raw
 * text exactly as typed, plus every resolved mention and every URL with
 * multibyte-correct offsets. This is what SyncMentions / SyncLinks persist
 * and what BodyRenderer renders from.
 */
class ParsedBodyData extends BaseData
{
    /**
     * @param  array<int, MentionData>  $mentions
     * @param  array<int, UrlData>  $urls
     */
    public function __construct(
        public string $raw,
        #[DataCollectionOf(MentionData::class)]
        public array $mentions,
        #[DataCollectionOf(UrlData::class)]
        public array $urls,
    ) {
    }
}
