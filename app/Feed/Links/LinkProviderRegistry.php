<?php

namespace App\Feed\Links;

use App\Data\Feed\LinkPreviewData;
use App\Feed\Links\Providers\GenericProvider;

/**
 * Resolves a URL to its LinkProvider driver (feed-spec §8). Drivers come from
 * config/feed.php — adding a provider is a driver class + one config line.
 * First match wins; GenericProvider (matches everything) is appended last as
 * the guaranteed fallback.
 */
class LinkProviderRegistry
{
    /**
     * @var list<LinkProvider>
     */
    private array $providers;

    public function __construct()
    {
        $classes = config('feed.providers', []);
        $classes[] = GenericProvider::class;

        $this->providers = array_map(
            fn (string $class): LinkProvider => app($class),
            array_values(array_unique($classes)),
        );
    }

    /**
     * The first driver that understands the URL (generic always matches).
     */
    public function resolve(string $url): LinkProvider
    {
        foreach ($this->providers as $provider) {
            if ($provider->matches($url)) {
                return $provider;
            }
        }

        return end($this->providers);
    }

    /**
     * Parse a URL through its driver — the zero-network chip payload.
     */
    public function parse(string $url): LinkPreviewData
    {
        return $this->resolve($url)->parse($url);
    }
}
