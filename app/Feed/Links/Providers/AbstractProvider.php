<?php

namespace App\Feed\Links\Providers;

use App\Data\Feed\LinkPreviewData;
use App\Feed\Links\LinkProvider;

/**
 * Shared plumbing for URL-string parsing: host matching that tolerates www /
 * m / mobile subdomains, clean path segmentation, and query access. Concrete
 * drivers declare their hosts and implement the path → handle/resource
 * mapping.
 */
abstract class AbstractProvider implements LinkProvider
{
    /**
     * The bare hosts this driver claims (e.g. ['instagram.com']).
     *
     * @return list<string>
     */
    abstract protected function hosts(): array;

    /**
     * Whether the URL's host is one of ours (any subdomain accepted).
     */
    public function matches(string $url): bool
    {
        $host = $this->host($url);

        foreach ($this->hosts() as $candidate) {
            if ($host === $candidate || str_ends_with($host, '.'.$candidate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The lowercase host, or '' for an unparsable URL.
     */
    protected function host(string $url): string
    {
        return strtolower((string) parse_url($url, PHP_URL_HOST));
    }

    /**
     * Non-empty, URL-decoded path segments.
     *
     * @return list<string>
     */
    protected function segments(string $url): array
    {
        $path = (string) parse_url($url, PHP_URL_PATH);

        return array_values(array_filter(
            array_map(urldecode(...), explode('/', $path)),
            fn (string $segment): bool => $segment !== ''
        ));
    }

    /**
     * One query-string value, or null.
     */
    protected function query(string $url, string $key): ?string
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $params);

        $value = $params[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Build the parse result under this driver's key.
     */
    protected function data(?string $handle = null, ?string $resourceId = null, ?string $canonicalUrl = null): LinkPreviewData
    {
        return new LinkPreviewData(
            provider: $this->key(),
            providerHandle: $handle !== null ? ltrim($handle, '@') : null,
            providerResourceId: $resourceId,
            canonicalUrl: $canonicalUrl,
        );
    }
}
