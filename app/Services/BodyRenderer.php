<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Talent;
use Illuminate\Support\Collection;

/**
 * Renders a raw post/comment body plus its PERSISTED mentions and links into
 * safe HTML (feed-spec §7–§8).
 *
 * Escape first, then inject markup — stored text is never trusted as HTML.
 * Mentions resolve the mentioned entity's CURRENT Username at render time
 * (ADR-F3), so renames never break. Bidi is first-class: every injected
 * mention/link is wrapped in <bdi> with Unicode isolates (LRI…PDI) around the
 * emitted raw text, so an Arabic body holding Latin handles or URLs never
 * reorders visually.
 */
class BodyRenderer
{
    private const LRI = "\u{2066}";

    private const PDI = "\u{2069}";

    /**
     * Render the body of a post or comment. Callers must have eager-loaded
     * `mentions.mentioned` and `linkedUrls.preview` — this method reads them
     * as-is (no lazy loads in a list).
     */
    public function render(Post|Comment $model): string
    {
        return $this->renderRaw(
            (string) $model->body,
            $model->mentions->loadMissing('mentioned'),
            $model->linkedUrls,
        );
    }

    /**
     * Render raw text with its mention and link rows. Tokens are replaced by
     * character range; everything between them is HTML-escaped. URL ranges
     * come from RE-PARSING the raw body (5V) — linked_urls stores appearance
     * order, and the stored preview URL is NORMALIZED (lowercased host,
     * trimmed slash), so locating it by string search would miss; pairing the
     * i-th parsed URL with the i-th linked_urls row is exact for trailing
     * slashes, adjacent URLs, and URLs at either end of the body.
     *
     * @param  Collection<int, \App\Models\Mention>  $mentions
     * @param  Collection<int, \App\Models\LinkedUrl>  $linkedUrls
     */
    public function renderRaw(string $raw, Collection $mentions, Collection $linkedUrls): string
    {
        $parsedUrls = app(\App\Services\RichTextParser::class)->parse($raw)->urls;
        $orderedPreviews = $linkedUrls->sortBy('position')->pluck('preview')->values();

        $tokens = collect()
            ->concat($mentions->map(fn ($mention): array => [
                'offset' => (int) $mention->start_offset,
                'length' => (int) $mention->length,
                'html' => $this->mentionHtml($mention->mentioned),
            ]))
            ->concat(collect($parsedUrls)->map(function ($url, int $index) use ($orderedPreviews): ?array {
                $preview = $orderedPreviews->get($index);

                if ($preview === null) {
                    return null;
                }

                return [
                    'offset' => $url->startOffset,
                    'length' => $url->length,
                    'html' => $this->linkHtml($preview),
                ];
            })->filter())
            ->sortBy('offset')
            ->values();

        $html = '';
        $cursor = 0;

        foreach ($tokens as $token) {
            if ($token['offset'] < $cursor) {
                continue; // Overlapping/stale token — skip rather than corrupt.
            }

            $html .= e(mb_substr($raw, $cursor, $token['offset'] - $cursor));
            $html .= $token['html'];
            $cursor = $token['offset'] + $token['length'];
        }

        $html .= e(mb_substr($raw, $cursor));

        return $html;
    }

    /**
     * A mention anchor: the CURRENT Username, isolated for bidi. A mentioned
     * entity that no longer exists degrades to plain text.
     */
    private function mentionHtml(mixed $mentioned): string
    {
        if (! $mentioned instanceof Talent && ! $mentioned instanceof Brand) {
            return '<bdi>'.self::LRI.'@'.self::PDI.'</bdi>';
        }

        $username = e($mentioned->feedUsername());
        $url = $mentioned instanceof Talent
            ? route('talent.public', ['slug' => $mentioned->slug])
            : route('brand.public', ['brand' => $mentioned->slug]);

        return '<bdi><a href="'.e($url).'" class="feed-mention">'
            .self::LRI.'@'.$username.self::PDI
            .'</a></bdi>';
    }

    /**
     * A link chip (5V): the raw URL is REPLACED — platform glyph (sprite
     * reference, monochrome) + the ChipLabel (handle / content noun /
     * enriched title / domain), isolated for bidi, nofollow/ugc. When
     * enrichment landed, a hover preview card (title + image) rides inside
     * the chip and fades in after a delay (CSS). Remote HTML never renders.
     */
    private function linkHtml(mixed $preview): string
    {
        $label = \App\Feed\Links\ChipLabel::for($preview);
        $glyph = in_array($preview->provider, [
            'instagram', 'tiktok', 'youtube', 'behance', 'vimeo', 'x', 'linkedin',
            'pinterest', 'dribbble', 'spotify', 'soundcloud', 'facebook', 'snapchat',
        ], true) ? $preview->provider : 'generic';

        $previewCard = '';

        if ($preview->status === 'ok' && (filled($preview->title) || filled($preview->image_url))) {
            $previewCard = '<span class="chip-preview" aria-hidden="true">'
                .(filled($preview->image_url) ? '<img src="'.e($preview->image_url).'" alt="" loading="lazy">' : '')
                .(filled($preview->title) ? '<span class="chip-preview-title">'.e(mb_substr((string) $preview->title, 0, 90)).'</span>' : '')
                .'</span>';
        }

        return '<bdi><a href="'.e($preview->url).'" class="feed-chip" target="_blank"'
            .' rel="noopener noreferrer nofollow ugc" data-provider="'.e($preview->provider).'">'
            .'<svg class="chip-glyph" aria-hidden="true"><use href="#glyph-'.e($glyph).'"></use></svg>'
            .self::LRI.e($label).self::PDI
            .$previewCard
            .'</a></bdi>';
    }
}
