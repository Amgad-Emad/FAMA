<?php

namespace App\Support;

use Illuminate\Support\Str;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/**
 * One page's search + share metadata (ADR-AO): title, description, canonical
 * URL, share picture, Open Graph type, robots and JSON-LD structured data.
 *
 * Pages build one with the fluent setters and pass it to `<x-fama.layout
 * :seo>`; the `partials/seo` view prints it. A page that passes nothing still
 * gets sensible defaults (site description, canonical, the default picture,
 * and the robots decision below).
 *
 * Robots: a page may be indexed ONLY when the site is indexable
 * (`seo.indexable`), its route is on the `seo.indexable_routes` allowlist, it
 * is not a private variant (`?preview` / `?visitor`), and the page itself did
 * not ask for noindex (e.g. a private profile).
 */
final class Seo
{
    private ?string $title = null;

    private ?string $description = null;

    private ?string $canonical = null;

    private ?string $image = null;

    private string $type = 'website';

    private bool $noindex = false;

    /** @var list<array<string, mixed>> */
    private array $jsonLd = [];

    /**
     * Start a page's metadata with the defaults.
     */
    public static function make(): self
    {
        return new self;
    }

    /**
     * The page title (" · Fama" is added by the layout, not here).
     */
    public function title(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * The description: plain text, whitespace collapsed, cut to 160 characters
     * at a word boundary. Blank keeps the site description.
     */
    public function description(?string $text): self
    {
        $text = Str::of(strip_tags(html_entity_decode((string) $text, ENT_QUOTES | ENT_HTML5)))->squish();
        $this->description = $text->isEmpty() ? null : $text->limit(160, '…', true)->value();

        return $this;
    }

    /**
     * The canonical URL (defaults to the current page without its query, on
     * the default locale — Arabic copy is paused, so /ar pages point home).
     */
    public function canonical(?string $url): self
    {
        $this->canonical = $url;

        return $this;
    }

    /**
     * The share picture (absolute URL, or a public path).
     */
    public function image(?string $url): self
    {
        $this->image = filled($url) ? $url : null;

        return $this;
    }

    /**
     * The Open Graph type: website, profile or article.
     */
    public function type(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Keep this page out of search (on top of the route allowlist).
     */
    public function noindex(bool $noindex = true): self
    {
        $this->noindex = $this->noindex || $noindex;

        return $this;
    }

    /**
     * Add one schema.org object (rendered as application/ld+json).
     *
     * @param  array<string, mixed>  $data
     */
    public function schema(array $data): self
    {
        $this->jsonLd[] = ['@context' => 'https://schema.org'] + self::prune($data);

        return $this;
    }

    // ----- Read (the partials/seo view) -----------------------------------------

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description ?? (string) config('seo.description');
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * The canonical URL: the one given, else this page's address on the default
     * locale with no query string.
     */
    public function getCanonical(): string
    {
        if ($this->canonical) {
            return $this->canonical;
        }
        $url = strtok(request()->url(), '?');

        return rescue(fn () => LaravelLocalization::getLocalizedURL(LaravelLocalization::getDefaultLocale(), $url, [], true) ?: $url, $url, false);
    }

    /**
     * The share picture as an absolute URL.
     */
    public function getImage(): string
    {
        $image = $this->image ?? (string) config('seo.default_image');

        return Str::startsWith($image, ['http://', 'https://']) ? $image : asset($image);
    }

    /**
     * Whether search engines may index this page (see the class docblock).
     */
    public function isIndexable(): bool
    {
        return ! $this->noindex && self::requestIndexable();
    }

    /**
     * The site-wide part of the robots decision, for the current request:
     * the site is indexable, the route is allowlisted, and no private flag
     * is in the query. Shared with the X-Robots-Tag middleware.
     */
    public static function requestIndexable(): bool
    {
        if (! config('seo.indexable')) {
            return false;
        }
        $request = request();
        foreach ((array) config('seo.private_query', []) as $flag) {
            if ($request->query->has($flag)) {
                return false;
            }
        }
        $name = $request->route()?->getName();

        return $name !== null && in_array($name, (array) config('seo.indexable_routes', []), true);
    }

    /**
     * The JSON-LD objects, ready to print (</script> can never close the tag).
     *
     * @return list<string>
     */
    public function getJsonLd(): array
    {
        return array_map(
            fn (array $data) => json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP),
            $this->jsonLd,
        );
    }

    /**
     * Drop empty values from structured data (search engines flag empty fields).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function prune(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = array_is_list($value)
                    ? array_values(array_filter(array_map(fn ($v) => is_array($v) ? self::prune($v) : $v, $value), fn ($v) => $v !== null && $v !== '' && $v !== []))
                    : self::prune($value);
            }
            if ($value !== null && $value !== '' && $value !== []) {
                $out[$key] = $value;
            }
        }

        return $out;
    }
}
