<?php

namespace App\Jobs;

use App\Feed\Links\UrlGuard;
use App\Models\LinkPreview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Best-effort link enrichment (ADR-F4): oEmbed where trusted, else Open Graph
 * tags. Success fills title/description/image and stamps ok + expires_at;
 * failure stamps failed and the parsed chip stays useful. A failure NEVER
 * surfaces to the user.
 *
 * Security (feed-spec §8): the UrlGuard runs before the first request and on
 * every redirect hop; redirects are followed manually (max 3); responses are
 * read through a hard byte cap; remote HTML is parsed, never rendered;
 * embed_html is stored only for allowlisted providers and reduced to a bare
 * iframe src check.
 */
class FetchLinkPreviewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 60, 300];

    public function __construct(public LinkPreview $preview)
    {
    }

    /**
     * Enrich the preview row in place.
     */
    public function handle(UrlGuard $guard): void
    {
        try {
            $enriched = $this->enrich($guard);
        } catch (Throwable $e) {
            if ($this->attempts() >= $this->tries) {
                $this->markFailed($e);

                return;
            }

            throw $e; // retry with backoff
        }

        $this->preview->forceFill($enriched + [
            'status' => 'ok',
            'fetched_at' => now(),
            'expires_at' => now()->addDays((int) config('feed.enrichment.ttl_days')),
        ])->save();
    }

    /**
     * The terminal-failure hook — keep the chip, mark the enrichment failed.
     */
    public function failed(?Throwable $exception): void
    {
        $this->markFailed($exception);
    }

    /**
     * Try oEmbed for trusted providers, then Open Graph.
     *
     * @return array<string, mixed>
     */
    private function enrich(UrlGuard $guard): array
    {
        $oembedEndpoint = config('feed.enrichment.oembed.'.$this->preview->provider);

        if (is_string($oembedEndpoint)) {
            $fields = $this->fromOembed($guard, $oembedEndpoint);

            if ($fields !== null) {
                return $fields;
            }
        }

        return $this->fromOpenGraph($guard);
    }

    /**
     * oEmbed metadata (title, thumbnail, sanitized embed for the allowlist).
     *
     * @return array<string, mixed>|null
     */
    private function fromOembed(UrlGuard $guard, string $endpoint): ?array
    {
        $url = $endpoint.'?'.http_build_query(['url' => $this->preview->url, 'format' => 'json']);
        $body = $this->fetch($guard, $url);

        $payload = json_decode($body, true);

        if (! is_array($payload) || ! isset($payload['title'])) {
            return null;
        }

        $fields = [
            'title' => mb_substr((string) $payload['title'], 0, 255),
            'description' => isset($payload['author_name']) ? mb_substr((string) $payload['author_name'], 0, 500) : null,
            'image_url' => isset($payload['thumbnail_url']) ? mb_substr((string) $payload['thumbnail_url'], 0, 2048) : null,
        ];

        if (in_array($this->preview->provider, config('feed.enrichment.embed_allowlist', []), true)
            && isset($payload['html'])) {
            $fields['embed_html'] = $this->sanitizeEmbed((string) $payload['html']);
        }

        return $fields;
    }

    /**
     * Open Graph metadata from the page's <meta> tags.
     *
     * @return array<string, mixed>
     */
    private function fromOpenGraph(UrlGuard $guard): array
    {
        $html = $this->fetch($guard, $this->preview->url);
        $tags = $this->extractMetaTags($html);

        return [
            'title' => isset($tags['og:title']) ? mb_substr($tags['og:title'], 0, 255) : $this->extractTitle($html),
            'description' => isset($tags['og:description']) ? mb_substr($tags['og:description'], 0, 500) : null,
            'image_url' => isset($tags['og:image']) ? mb_substr($tags['og:image'], 0, 2048) : null,
            'canonical_url' => isset($tags['og:url']) ? mb_substr($tags['og:url'], 0, 2048) : null,
        ];
    }

    /**
     * Fetch a URL with the full guard rail: per-hop SSRF validation, manual
     * redirects (max 3), 5s timeout, hard byte cap.
     */
    private function fetch(UrlGuard $guard, string $url): string
    {
        $maxRedirects = (int) config('feed.enrichment.max_redirects');
        $maxBytes = (int) config('feed.enrichment.max_bytes');

        for ($hop = 0; $hop <= $maxRedirects; $hop++) {
            $guard->assertSafe($url);

            $response = Http::timeout((int) config('feed.enrichment.timeout_seconds'))
                ->withOptions(['allow_redirects' => false])
                ->withHeaders(['User-Agent' => 'FamaLinkPreview/1.0 (+https://fama.example)'])
                ->get($url);

            if ($response->redirect()) {
                $location = $response->header('Location');

                if ($location === '' || $location === null) {
                    break;
                }

                $url = $this->absolutize($location, $url);

                continue;
            }

            $response->throwIf($response->failed());

            return mb_strcut($response->body(), 0, $maxBytes);
        }

        throw new \RuntimeException('Too many redirects.');
    }

    /**
     * Resolve a possibly-relative redirect Location against its base URL.
     */
    private function absolutize(string $location, string $base): string
    {
        if (parse_url($location, PHP_URL_SCHEME) !== null) {
            return $location;
        }

        $parts = parse_url($base);
        $origin = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '');

        return $origin.(str_starts_with($location, '/') ? $location : '/'.$location);
    }

    /**
     * Pull og:* properties out of raw HTML without rendering it.
     *
     * @return array<string, string>
     */
    private function extractMetaTags(string $html): array
    {
        $tags = [];

        if (preg_match_all('/<meta[^>]+property=["\'](og:[a-z:_]+)["\'][^>]+content=["\']([^"\']*)["\']/i', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $tags[strtolower($match[1])] = html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5);
            }
        }

        // content-before-property attribute order.
        if (preg_match_all('/<meta[^>]+content=["\']([^"\']*)["\'][^>]+property=["\'](og:[a-z:_]+)["\']/i', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $tags[strtolower($match[2])] ??= html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5);
            }
        }

        return $tags;
    }

    /**
     * The <title> fallback when no og:title exists.
     */
    private function extractTitle(string $html): ?string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $match)) {
            $title = trim(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5));

            return $title !== '' ? mb_substr($title, 0, 255) : null;
        }

        return null;
    }

    /**
     * Reduce provider embed HTML to a single vetted iframe (allowlisted
     * providers only) — remote markup is never stored verbatim.
     */
    private function sanitizeEmbed(string $html): ?string
    {
        if (! preg_match('/<iframe[^>]+src=["\']([^"\']+)["\']/i', $html, $match)) {
            return null;
        }

        $src = $match[1];
        $host = strtolower((string) parse_url($src, PHP_URL_HOST));

        $trusted = ['www.youtube.com', 'youtube.com', 'player.vimeo.com'];

        if (! in_array($host, $trusted, true) || ! str_starts_with($src, 'https://')) {
            return null;
        }

        return '<iframe src="'.e($src).'" loading="lazy" allowfullscreen frameborder="0"></iframe>';
    }

    /**
     * Stamp the row failed (the parsed chip stays) and log quietly.
     */
    private function markFailed(?Throwable $exception): void
    {
        $this->preview->forceFill(['status' => 'failed', 'fetched_at' => now()])->save();

        Log::channel('feed')->info('Link preview enrichment failed', [
            'url' => $this->preview->url,
            'provider' => $this->preview->provider,
            'error' => $exception?->getMessage(),
        ]);
    }
}
