<?php

namespace App\Actions\Feed;

use App\Feed\Links\LinkProviderRegistry;
use App\Jobs\FetchLinkPreviewJob;
use App\Models\LinkPreview;

/**
 * Find-or-create the deduplicated LinkPreview row for a URL (ADR-F4): hash
 * the normalized URL, parse provider/handle/resource through the driver
 * registry (zero network — the chip is instantly useful), persist as pending,
 * and dispatch FetchLinkPreviewJob for best-effort enrichment. The dispatch
 * rides after the caller's transaction commits.
 */
class ResolveLinkPreview
{
    public function __construct(private readonly LinkProviderRegistry $registry)
    {
    }

    /**
     * Return the preview row for the URL, creating + enriching on first sight.
     */
    public function __invoke(string $url): LinkPreview
    {
        $normalized = $this->normalize($url);

        $preview = LinkPreview::query()->firstOrCreate(
            ['url_hash' => hash('sha256', $normalized)],
            $this->pendingAttributes($normalized),
        );

        if ($preview->wasRecentlyCreated) {
            FetchLinkPreviewJob::dispatch($preview)->afterCommit();
        }

        return $preview;
    }

    /**
     * The driver-parsed initial attributes for a fresh row.
     *
     * @return array<string, mixed>
     */
    private function pendingAttributes(string $normalized): array
    {
        $parsed = $this->registry->parse($normalized);

        return [
            'url' => $normalized,
            'canonical_url' => $parsed->canonicalUrl,
            'provider' => $parsed->provider,
            'provider_handle' => $parsed->providerHandle,
            'provider_resource_id' => $parsed->providerResourceId,
            'status' => 'pending',
        ];
    }

    /**
     * Normalize for dedupe: lowercase scheme+host, strip fragments and the
     * trailing slash on bare paths; query strings are preserved (they can be
     * the resource id).
     */
    private function normalize(string $url): string
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return $url;
        }

        $normalized = strtolower($parts['scheme']).'://'.strtolower($parts['host']);

        if (isset($parts['port'])) {
            $normalized .= ':'.$parts['port'];
        }

        $path = rtrim($parts['path'] ?? '', '/');
        $normalized .= $path;

        if (isset($parts['query'])) {
            $normalized .= '?'.$parts['query'];
        }

        return $normalized;
    }
}
