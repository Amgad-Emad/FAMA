<?php

namespace App\Data\Feed;

use App\Data\BaseData;

/**
 * One resolved @mention inside a raw body (ADR-F3): who is mentioned (morph
 * alias + id + the Username that matched at parse time) and where — a
 * multibyte-correct character offset + length into the raw text. The stored
 * row keeps only type/id/offset/length; the username here is parse-time
 * context (rendering re-resolves the current Username).
 */
class MentionData extends BaseData
{
    public function __construct(
        public string $mentionedType,
        public int $mentionedId,
        public string $username,
        public int $startOffset,
        public int $length,
    ) {
    }
}
