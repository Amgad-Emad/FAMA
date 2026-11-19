<?php

namespace App\Support;

use App\Models\Project;
use App\Support\Html\BriefSanitizer;
use InvalidArgumentException;

/**
 * Free-form project blocks (ADR-AN) — THE home of the block shapes, port of the
 * prototype's project-editor.js (`fresh()` / MENU, 24 Oct – 13 Nov 2026).
 *
 * A project's story is an ordered list stored on `projects.blocks`:
 *
 *   text        {t, v}                       a paragraph (the first one before any heading is the lead)
 *   heading     {t, v}                       starts a chapter
 *   quote       {t, v, by}
 *   image       {t, m, cap}                  m = an id in the project's `media` collection
 *   gallery     {t, items: [{m, cap}]}       ≤ 24; 4+ become the scrolling "Cases" gallery
 *   beforeafter {t, mb, ma, lb, la, cap}     one frame twice
 *   look        {t, m, title, items: [{n, d, x, y}]}   ≤ 8 tagged pieces, x/y in % of the photo
 *   video       {t, url, cap}                a YouTube or Vimeo link
 *   embed       {t, url}                     an Instagram / TikTok / Behance / YouTube post
 *   results     {t}                          WHERE the Results sit — the rows stay on `projects.results`
 *   credits     {t}                          WHERE the Credits sit in the editor — rows stay on `project_credits`
 *
 * Pictures are never URLs: a block points at media the project owns, so a
 * tampered payload cannot show someone else's file. `summary` and `body` stay
 * as DERIVED projections of the blocks (the feed's project spread, cards and
 * the API still read them) — see {@see self::lead()} and {@see self::bodyHtml()}.
 */
final class ProjectBlocks
{
    /** Every block type, in the editor menu's order (Write · Show · Prove). */
    public const TYPES = ['text', 'heading', 'quote', 'image', 'gallery', 'beforeafter', 'look', 'video', 'embed', 'results', 'credits'];

    /** Blocks a project holds at most (a long case study stays well under it). */
    public const MAX_BLOCKS = 80;

    /** Pictures in one Photos block. */
    public const MAX_GALLERY = 24;

    /** Tagged pieces on one look. */
    public const MAX_LOOK_ITEMS = 8;

    /** Field length caps, shared by the Form Request and normalize(). */
    public const LIMITS = [
        'text' => 5000, 'heading' => 160, 'quote' => 600, 'by' => 160, 'cap' => 200,
        'label' => 40, 'title' => 80, 'piece' => 80, 'detail' => 160, 'url' => 2048,
    ];

    /**
     * The validation rules for a `blocks` payload (shape + length). Media
     * ownership and link hosts are checked by {@see self::normalize()}.
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        $l = self::LIMITS;

        return [
            'blocks' => ['sometimes', 'nullable', 'array', 'max:'.self::MAX_BLOCKS],
            'blocks.*' => ['array'],
            'blocks.*.t' => ['required', 'string', 'in:'.implode(',', self::TYPES)],
            'blocks.*.v' => ['nullable', 'string', 'max:'.$l['text']],
            'blocks.*.by' => ['nullable', 'string', 'max:'.$l['by']],
            'blocks.*.cap' => ['nullable', 'string', 'max:'.$l['cap']],
            'blocks.*.m' => ['nullable', 'integer'],
            'blocks.*.mb' => ['nullable', 'integer'],
            'blocks.*.ma' => ['nullable', 'integer'],
            'blocks.*.lb' => ['nullable', 'string', 'max:'.$l['label']],
            'blocks.*.la' => ['nullable', 'string', 'max:'.$l['label']],
            'blocks.*.title' => ['nullable', 'string', 'max:'.$l['title']],
            'blocks.*.url' => ['nullable', 'string', 'max:'.$l['url']],
            'blocks.*.items' => ['nullable', 'array', 'max:'.self::MAX_GALLERY],
            'blocks.*.items.*' => ['array'],
            'blocks.*.items.*.m' => ['nullable', 'integer'],
            'blocks.*.items.*.cap' => ['nullable', 'string', 'max:'.$l['cap']],
            'blocks.*.items.*.n' => ['nullable', 'string', 'max:'.$l['piece']],
            'blocks.*.items.*.d' => ['nullable', 'string', 'max:'.$l['detail']],
            'blocks.*.items.*.x' => ['nullable', 'numeric'],
            'blocks.*.items.*.y' => ['nullable', 'numeric'],
        ];
    }

    /**
     * Normalise a validated payload to the stored shape: unknown keys dropped,
     * strings trimmed, pins clamped, one Results and one Credits marker at
     * most. A picture id the project does not own, or a video link that is not
     * YouTube / Vimeo, is a domain error (422) — never silently kept.
     *
     * @param  array<int, mixed>  $blocks
     * @param  list<int>  $ownedMediaIds  ids in the project's `media` collection
     * @return list<array<string, mixed>>
     */
    public static function normalize(array $blocks, array $ownedMediaIds): array
    {
        $owned = array_flip(array_map('intval', $ownedMediaIds));
        $media = function (mixed $id) use ($owned): ?int {
            if ($id === null || $id === '') {
                return null;
            }
            if (! isset($owned[(int) $id])) {
                throw new InvalidArgumentException(__('That picture is not on this project.'));
            }

            return (int) $id;
        };
        $str = fn (mixed $value): string => trim(preg_replace('/[^\S\n]+/u', ' ', (string) ($value ?? '')));
        $line = fn (mixed $value): string => trim(preg_replace('/\s+/u', ' ', (string) ($value ?? '')));
        $pct = fn (mixed $value): int => (int) max(3, min(97, round(is_numeric($value) ? (float) $value : 50)));

        $out = [];
        $seen = [];

        foreach (array_slice(array_values($blocks), 0, self::MAX_BLOCKS) as $block) {
            if (! is_array($block) || ! in_array($block['t'] ?? null, self::TYPES, true)) {
                continue;
            }
            $t = $block['t'];

            $out[] = match ($t) {
                'text' => ['t' => $t, 'v' => $str($block['v'] ?? '')],
                'heading' => ['t' => $t, 'v' => $line($block['v'] ?? '')],
                'quote' => ['t' => $t, 'v' => $line($block['v'] ?? ''), 'by' => $line($block['by'] ?? '')],
                'image' => ['t' => $t, 'm' => $media($block['m'] ?? null), 'cap' => $line($block['cap'] ?? '')],
                'gallery' => ['t' => $t, 'items' => collect($block['items'] ?? [])
                    ->filter(fn ($item) => is_array($item) && ($item['m'] ?? null) !== null && ($item['m'] ?? '') !== '')
                    ->take(self::MAX_GALLERY)
                    ->map(fn (array $item) => ['m' => $media($item['m']), 'cap' => $line($item['cap'] ?? '')])
                    ->values()->all()],
                'beforeafter' => ['t' => $t, 'mb' => $media($block['mb'] ?? null), 'ma' => $media($block['ma'] ?? null),
                    'lb' => $line($block['lb'] ?? '') ?: 'Before', 'la' => $line($block['la'] ?? '') ?: 'After', 'cap' => $line($block['cap'] ?? '')],
                'look' => ['t' => $t, 'm' => $media($block['m'] ?? null), 'title' => $line($block['title'] ?? ''),
                    'items' => collect($block['items'] ?? [])->filter(fn ($item) => is_array($item))->take(self::MAX_LOOK_ITEMS)
                        ->map(fn (array $item) => ['n' => $line($item['n'] ?? ''), 'd' => $line($item['d'] ?? ''), 'x' => $pct($item['x'] ?? 50), 'y' => $pct($item['y'] ?? 50)])
                        ->values()->all()],
                'video' => ['t' => $t, 'url' => self::videoUrl($block['url'] ?? ''), 'cap' => $line($block['cap'] ?? '')],
                'embed' => ['t' => $t, 'url' => self::linkUrl($block['url'] ?? '')],
                default => ['t' => $t], // results / credits: placement markers
            };

            // One Results and one Credits block: their rows are the project's, not the block's.
            if (in_array($t, ['results', 'credits'], true)) {
                if (isset($seen[$t])) {
                    array_pop($out);
                }
                $seen[$t] = true;
            }
        }

        return $out;
    }

    /**
     * A project written before blocks, as blocks: the opening line, the photos
     * in their chosen order, the story's paragraphs (headings stay headings,
     * quotes stay quotes), then Results and Credits where they were shown.
     *
     * @return list<array<string, mixed>>
     */
    public static function fromLegacy(Project $project): array
    {
        $blocks = [];
        $lead = self::plain((string) $project->getTranslation('summary', 'en', false));
        if ($lead !== '') {
            $blocks[] = ['t' => 'text', 'v' => $lead];
        }

        $items = $project->orderedMedia()->map(fn ($m) => ['m' => (int) $m->id, 'cap' => ''])->values()->all();
        if ($items !== []) {
            $blocks[] = ['t' => 'gallery', 'items' => array_slice($items, 0, self::MAX_GALLERY)];
            // More than one Photos block's worth: the rest follow in another.
            foreach (array_chunk(array_slice($items, self::MAX_GALLERY), self::MAX_GALLERY) as $more) {
                $blocks[] = ['t' => 'gallery', 'items' => $more];
            }
        }

        array_push($blocks, ...self::fromHtml((string) $project->getTranslation('body', 'en', false)));

        if ($project->resultRows() !== []) {
            $blocks[] = ['t' => 'results'];
        }
        if ($project->relationLoaded('credits') ? $project->credits->isNotEmpty() : $project->credits()->exists()) {
            $blocks[] = ['t' => 'credits'];
        }

        return $blocks;
    }

    /**
     * Rich story HTML → text / heading / quote blocks (one per paragraph).
     *
     * @return list<array<string, mixed>>
     */
    public static function fromHtml(string $html): array
    {
        $html = trim(BriefSanitizer::clean($html));
        if ($html === '' || trim(strip_tags($html)) === '') {
            return [];
        }
        // Plain text written before the story was rich: blank lines split paragraphs.
        if (! preg_match('/<(p|div|br|li|h[1-6]|blockquote)\b/i', $html)) {
            return collect(preg_split('/\n\s*\n+/', $html))
                ->map(fn ($p) => self::plain($p))->filter()->map(fn ($p) => ['t' => 'text', 'v' => $p])->values()->all();
        }

        $doc = new \DOMDocument;
        @$doc->loadHTML('<?xml encoding="UTF-8"><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $root = $doc->getElementsByTagName('div')->item(0);
        $out = [];
        $loose = '';
        $flush = function () use (&$loose, &$out): void {
            if (($text = self::plain($loose)) !== '') {
                $out[] = ['t' => 'text', 'v' => $text];
            }
            $loose = '';
        };

        foreach ($root?->childNodes ?? [] as $node) {
            $tag = $node instanceof \DOMElement ? strtolower($node->tagName) : '#text';
            $text = self::plain($node->textContent ?? '');
            if (in_array($tag, ['#text', 'b', 'strong', 'i', 'em', 'a', 'span', 'u'], true)) {
                $loose .= ' '.($node->textContent ?? '');

                continue;
            }
            $flush();
            if ($tag === 'br' || $text === '') {
                continue;
            }
            if (preg_match('/^h[1-6]$/', $tag)) {
                $out[] = ['t' => 'heading', 'v' => mb_substr($text, 0, self::LIMITS['heading'])];
            } elseif ($tag === 'blockquote') {
                $out[] = ['t' => 'quote', 'v' => mb_substr($text, 0, self::LIMITS['quote']), 'by' => ''];
            } elseif (in_array($tag, ['ul', 'ol'], true)) {
                foreach ($node->childNodes as $li) {
                    if (($item = self::plain($li->textContent ?? '')) !== '') {
                        $out[] = ['t' => 'text', 'v' => '• '.$item];
                    }
                }
            } elseif ($node instanceof \DOMElement && $node->childNodes->length === 1 && in_array(strtolower($node->firstChild->nodeName), ['strong', 'b'], true) && mb_strlen($text) <= self::LIMITS['heading']) {
                // A paragraph that is one bold line reads as a chapter heading.
                $out[] = ['t' => 'heading', 'v' => $text];
            } else {
                $out[] = ['t' => 'text', 'v' => $text];
            }
        }
        $flush();

        return $out;
    }

    /**
     * The lead: the first text block before any heading ('' when none) — the
     * prototype's opening line, and the project's `summary` projection.
     *
     * @param  list<array<string, mixed>>  $blocks
     */
    public static function lead(array $blocks): string
    {
        foreach ($blocks as $block) {
            if (($block['t'] ?? '') === 'heading' && trim((string) ($block['v'] ?? '')) !== '') {
                return '';
            }
            if (($block['t'] ?? '') === 'text' && trim((string) ($block['v'] ?? '')) !== '') {
                return trim((string) $block['v']);
            }
        }

        return '';
    }

    /**
     * The project's one-line description (`summary`): the lead, else — for a
     * story that opens with a chapter — its first paragraph.
     *
     * @param  list<array<string, mixed>>  $blocks
     */
    public static function summary(array $blocks): string
    {
        if (($lead = self::lead($blocks)) !== '') {
            return $lead;
        }
        foreach ($blocks as $block) {
            if (($block['t'] ?? '') === 'text' && trim((string) ($block['v'] ?? '')) !== '') {
                return trim((string) $block['v']);
            }
        }

        return '';
    }

    /**
     * Everything written AFTER the lead, as safe HTML — the project's `body`
     * projection (what the API and older readers show as the story).
     *
     * @param  list<array<string, mixed>>  $blocks
     */
    public static function bodyHtml(array $blocks): string
    {
        $lead = self::lead($blocks);
        $leadUsed = $lead === '';
        $html = '';

        foreach ($blocks as $block) {
            $v = trim((string) ($block['v'] ?? ''));
            if ($v === '') {
                continue;
            }
            if (! $leadUsed && $block['t'] === 'text' && $v === $lead) {
                $leadUsed = true;

                continue;
            }
            $html .= match ($block['t']) {
                'text' => '<p>'.nl2br(e($v), false).'</p>',
                // Inside the shared sanitiser's allowlist (no h2 / blockquote there).
                'heading' => '<p><strong>'.e($v).'</strong></p>',
                'quote' => '<p><em>“'.e($v).'”</em>'.(filled($block['by'] ?? '') ? ' — '.e($block['by']) : '').'</p>',
                default => '',
            };
        }

        return $html;
    }

    /**
     * Every media id the blocks show, in first-appearance order — the order
     * the project's `media` collection is kept in, so the lead image and the
     * cards follow the story.
     *
     * @param  list<array<string, mixed>>  $blocks
     * @return list<int>
     */
    public static function mediaIds(array $blocks): array
    {
        $ids = [];
        foreach ($blocks as $block) {
            foreach (['m', 'mb', 'ma'] as $key) {
                if (! empty($block[$key]) && in_array($block['t'], ['image', 'beforeafter', 'look'], true)) {
                    $ids[] = (int) $block[$key];
                }
            }
            if ($block['t'] === 'gallery') {
                foreach ($block['items'] ?? [] as $item) {
                    $ids[] = (int) $item['m'];
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Whether the project has something to look at besides its cover — the
     * prototype's Publish check ("Add a cover, or some pictures or a video").
     *
     * @param  list<array<string, mixed>>  $blocks
     */
    public static function hasVisual(array $blocks): bool
    {
        foreach ($blocks as $block) {
            if (match ($block['t']) {
                'image', 'look' => ! empty($block['m']),
                'beforeafter' => ! empty($block['ma']) || ! empty($block['mb']),
                'gallery' => ! empty($block['items']),
                'video', 'embed' => filled($block['url'] ?? ''),
                default => false,
            }) {
                return true;
            }
        }

        return false;
    }

    /**
     * The platform a link belongs to, as the page names it ('' when unknown).
     */
    public static function platform(string $url): string
    {
        return match (true) {
            str_contains($url, 'instagram') => 'Instagram',
            str_contains($url, 'tiktok') => 'TikTok',
            str_contains($url, 'behance') => 'Behance',
            (bool) preg_match('/youtu\.?be/', $url) => 'YouTube',
            str_contains($url, 'vimeo') => 'Vimeo',
            default => '',
        };
    }

    /**
     * The account a social link belongs to (@handle, or the Instagram path),
     * else the platform's name in lower case — the prototype's card heading.
     */
    public static function handle(string $url): string
    {
        if (preg_match('/@([\w.\-]+)/', $url, $m) || preg_match('#instagram\.com/(?:reel/)?([\w.\-]+?)(?:-|/|$)#', $url, $m)) {
            return $m[1];
        }

        return strtolower(self::platform($url) ?: 'post');
    }

    /**
     * The player URL for a YouTube / Vimeo link (privacy-enhanced YouTube),
     * or null when the link is neither.
     */
    public static function player(string $url): ?string
    {
        if (preg_match('#(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/)|youtu\.be/)([\w-]{6,20})#', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1].'?autoplay=1&rel=0';
        }
        if (preg_match('#vimeo\.com/(?:video/)?(\d{5,12})#', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1].'?autoplay=1';
        }

        return null;
    }

    /**
     * A YouTube video's still (Vimeo has none without an API call — the page
     * falls back to the project's lead picture).
     */
    public static function poster(string $url): ?string
    {
        return preg_match('#(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/)|youtu\.be/)([\w-]{6,20})#', $url, $m)
            ? 'https://i.ytimg.com/vi/'.$m[1].'/hqdefault.jpg'
            : null;
    }

    /**
     * A video link, kept only when it is YouTube or Vimeo ('' clears it).
     */
    private static function videoUrl(mixed $url): string
    {
        $url = self::linkUrl($url);
        if ($url !== '' && ! preg_match('#^https?://([\w-]+\.)*(youtube\.com|youtu\.be|vimeo\.com)/#i', $url)) {
            throw new InvalidArgumentException(__('Paste a YouTube or Vimeo link.'));
        }

        return $url;
    }

    /**
     * A web link: http(s) only ('' clears it).
     */
    private static function linkUrl(mixed $url): string
    {
        $url = trim((string) ($url ?? ''));
        if ($url === '') {
            return '';
        }
        if (! preg_match('#^https?://[^\s/$.?\#].[^\s]*$#i', $url)) {
            throw new InvalidArgumentException(__('Enter a full link, like https://studio.com/work.'));
        }

        return $url;
    }

    /**
     * Visible text of a fragment of HTML, whitespace collapsed.
     */
    private static function plain(string $html): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(
            str_replace(['</p>', '<br>', '<br/>', '<br />'], ' ', $html)
        ), ENT_QUOTES | ENT_HTML5)));
    }
}
