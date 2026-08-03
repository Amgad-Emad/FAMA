<?php

namespace App\Http\Resources\Feed;

use App\Models\LinkPreview;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The link chip payload (ADR-F4): the zero-network parse (provider + handle /
 * resource) that renders instantly, plus whatever enrichment has landed.
 */
class LinkChipResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var LinkPreview $preview */
        $preview = $this->resource;

        return [
            'provider' => $preview->provider,
            'handle' => $preview->provider_handle,
            'resource_id' => $preview->provider_resource_id,
            'url' => $preview->url,
            'canonical_url' => $preview->canonical_url,
            'title' => $preview->title,
            'description' => $preview->description,
            'image_url' => $preview->image_url,
            'status' => $preview->status,
        ];
    }
}
