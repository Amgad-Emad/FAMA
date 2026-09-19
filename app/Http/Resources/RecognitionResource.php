<?php

namespace App\Http\Resources;

use App\Models\Recognition;
use Illuminate\Http\Request;

/**
 * A peer vouch (ADR-Z). The author is always a talent on the platform, so the
 * payload carries their slug — a recognition names a real, reachable person,
 * which is what separates it from an anonymous review.
 *
 * @mixin Recognition
 */
class RecognitionResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'relationship' => $this->relationship,
            'body' => $this->body,
            'status' => (string) $this->status,
            'is_approved' => (bool) $this->is_approved,
            'recognized_at' => $this->recognized_at?->toIso8601String(),
            'author' => $this->whenLoaded('author', fn () => [
                'slug' => $this->author->slug,
                'display_name' => $this->author->display_name,
                'avatar_url' => $this->author->avatar_url,
            ]),
            'talent' => $this->whenLoaded('talent', fn () => [
                'slug' => $this->talent->slug,
                'display_name' => $this->talent->display_name,
            ]),
        ];
    }
}
