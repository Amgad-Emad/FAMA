<?php

namespace App\Http\Resources\Feed;

use App\Feed\Actor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The feed's actor identity shape (feed-spec §2): morph type + Username +
 * display name + avatar. Callers must eager-load `media` on the underlying
 * model — the avatar accessor resolves from it.
 */
class ActorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Actor|null $actor */
        $actor = $this->resource;

        if ($actor === null) {
            return [];
        }

        return [
            'type' => $actor->getMorphClass(),
            'id' => $actor->getKey(),
            'username' => $actor->feedUsername(),
            'display_name' => $actor->feedDisplayName(),
            'avatar_url' => $actor->feedAvatarUrl(),
        ];
    }
}
