<?php

namespace App\Data\Feed;

use App\Data\BaseData;
use App\Feed\Actor;

/**
 * One follow suggestion (5Z): the entity, its top two Skills, its follower
 * count, and a truthful reason line (null = the line is simply omitted —
 * never fabricated).
 */
class SuggestionData extends BaseData
{
    /**
     * @param  array<int, array{name: mixed, slug: string}>  $skills
     */
    public function __construct(
        public Actor $entity,
        public array $skills,
        public int $followersCount,
        public ?string $reason,
    ) {
    }

    /**
     * The wire shape for the rails.
     *
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'type' => $this->entity->getMorphClass(),
            'id' => $this->entity->getKey(),
            'username' => $this->entity->feedUsername(),
            'display_name' => $this->entity->feedDisplayName(),
            'avatar_url' => $this->entity->feedAvatarUrl(),
            'headline' => $this->entity->headline ?? null,
            'city' => $this->entity->base_city ?? null,
            'sector' => $this->entity->industry ?? null,
            'skills' => $this->skills,
            'followers_count' => $this->followersCount,
            'reason' => $this->reason,
        ];
    }
}
