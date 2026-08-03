<?php

namespace App\Feed\Links;

use InvalidArgumentException;

/**
 * The SSRF guard for link enrichment (feed-spec §8 — mandatory, not polish).
 *
 * Only http/https; the host's DNS answers are resolved BEFORE connecting and
 * every address must be public — private (10/8, 172.16/12, 192.168/16),
 * loopback, link-local and reserved ranges are rejected, as are IP-literal
 * hosts in those ranges. FetchLinkPreviewJob re-runs the guard on every
 * redirect hop.
 */
class UrlGuard
{
    /**
     * Validate a URL for outbound fetching; throws on anything unsafe.
     *
     * @throws InvalidArgumentException
     */
    public function assertSafe(string $url): void
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['host'])) {
            throw new InvalidArgumentException('Unparsable URL.');
        }

        $scheme = strtolower($parts['scheme'] ?? '');

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException('Only http and https URLs are fetched.');
        }

        $host = strtolower($parts['host']);

        foreach ($this->resolveAddresses($host) as $address) {
            if (! $this->isPublicAddress($address)) {
                throw new InvalidArgumentException('URL resolves to a non-public address.');
            }
        }
    }

    /**
     * The host's addresses: the literal itself for IP hosts, else its DNS
     * A/AAAA answers. An unresolvable host is rejected (empty = unsafe).
     *
     * @return list<string>
     */
    private function resolveAddresses(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        $addresses = [];

        foreach (dns_get_record($host, DNS_A | DNS_AAAA) ?: [] as $record) {
            $address = $record['ip'] ?? $record['ipv6'] ?? null;

            if (is_string($address)) {
                $addresses[] = $address;
            }
        }

        if ($addresses === []) {
            throw new InvalidArgumentException('URL host does not resolve.');
        }

        return $addresses;
    }

    /**
     * Whether an address is public — rejects private, loopback, link-local
     * and reserved ranges in one filter_var pass.
     */
    private function isPublicAddress(string $address): bool
    {
        return filter_var(
            $address,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) !== false;
    }
}
