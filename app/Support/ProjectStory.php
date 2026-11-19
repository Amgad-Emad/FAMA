<?php

namespace App\Support;

use App\Models\Project;

/**
 * Lays a project's blocks out as the public page (ADR-AN) — port of the
 * prototype's project.html `render()` (24 Oct – 13 Nov 2026). The blocks say
 * WHAT; this decides HOW, by the prototype's rules:
 *
 *  - no heading at all → the SHORT page: the opening line beside the facts,
 *    every picture in one magazine grid (the first big), then the rest in order;
 *  - with headings → the LONG page: the first text before any heading is the
 *    lead; every heading opens a numbered chapter; a heading followed by 4+
 *    photos (optionally with one line of text between) BECOMES the scrolling
 *    "Cases" gallery under the chapter's name; an image followed by text sits
 *    beside it (a spread); photos lay out by count (1 wide · 2 side by side ·
 *    3 = one wide then a pair · 4+ = Cases); social posts next to each other
 *    share one row; Credits never render here (the team band shows them).
 *
 * The output is a flat list of pieces the Blade partial
 * `public/partials/project-story` draws, so the rules stay out of the view.
 */
final class ProjectStory
{
    /** The Cases gallery's rhythm of widths and shapes (prototype W / AR). */
    private const CASE_W = ['w-l', 'w-s', 'w-m', 'w-l', 'w-s'];

    private const CASE_AR = ['4/3', '4/5', '1', '4/5', '3/4'];

    /** @var array<int, array{url: string, video: bool, mime: string}> */
    private array $media = [];

    private int $total = 0;

    private int $no = 0;

    /**
     * @param  list<array<string, mixed>>  $blocks
     */
    private function __construct(private readonly Project $project, private readonly array $blocks)
    {
        foreach ($project->allMedia() as $item) {
            $this->media[(int) $item->id] = [
                'url' => $item->getUrl(),
                'video' => str_starts_with((string) $item->mime_type, 'video/'),
                'mime' => (string) $item->mime_type,
            ];
        }
    }

    /**
     * The page for a project: ['short' => bool, 'lead' => string,
     * 'photos' => figs (short page), 'pieces' => [...]].
     *
     * @return array{short: bool, lead: string, photos: list<array<string, mixed>>, pieces: list<array<string, mixed>>}
     */
    public static function for(Project $project): array
    {
        $blocks = array_values(array_filter(
            $project->storyBlocks(),
            fn (array $b) => ! (in_array($b['t'], ['text', 'heading'], true) && trim((string) ($b['v'] ?? '')) === ''),
        ));

        return (new self($project, $blocks))->build();
    }

    /**
     * @return array{short: bool, lead: string, photos: list<array<string, mixed>>, pieces: list<array<string, mixed>>}
     */
    private function build(): array
    {
        $B = $this->blocks;
        foreach ($B as $b) {
            $this->total += match ($b['t']) {
                'image' => isset($this->media[$b['m'] ?? 0]) ? 1 : 0,
                'gallery' => count($this->items($b)),
                default => 0,
            };
        }

        $short = ! collect($B)->contains(fn ($b) => $b['t'] === 'heading');

        return $short ? $this->shortPage($B) : $this->longPage($B);
    }

    /**
     * The page with chapters.
     *
     * @param  list<array<string, mixed>>  $B
     */
    private function longPage(array $B): array
    {
        $pieces = [];
        $lead = '';
        $ch = 0;
        $open = false;
        $leadDone = false;
        $close = function () use (&$pieces, &$open): void {
            if ($open) {
                $pieces[] = ['k' => 'close'];
                $open = false;
            }
        };

        for ($i = 0, $n = count($B); $i < $n;) {
            $b = $B[$i];
            if ($b['t'] === 'credits') {
                $i++;

                continue;
            }
            if (! $leadDone && $b['t'] === 'text' && $ch === 0) {
                $lead = trim((string) $b['v']);
                $leadDone = true;
                $i++;

                continue;
            }
            $leadDone = true;

            if ($b['t'] === 'heading') {
                $close();
                $ch++;
                $nx = $B[$i + 1] ?? null;
                $nx2 = $B[$i + 2] ?? null;
                $gallery = $nx && $nx['t'] === 'gallery' && count($this->items($nx)) >= 4 ? $nx
                    : ($nx && $nx['t'] === 'text' && $nx2 && $nx2['t'] === 'gallery' && count($this->items($nx2)) >= 4 ? $nx2 : null);

                $pieces[] = ['k' => 'open'];
                $open = true;
                if ($gallery) {
                    $pieces[] = $this->cases($gallery, [
                        'k' => sprintf('%s %02d', __('CHAPTER'), $ch),
                        'title' => (string) $b['v'],
                        'intro' => $nx['t'] === 'text' ? (string) $nx['v'] : '',
                    ]);
                    $i += $nx['t'] === 'text' ? 3 : 2;

                    continue;
                }
                $pieces[] = ['k' => 'chapter', 'n' => sprintf('%s %02d', __('CHAPTER'), $ch), 'title' => (string) $b['v']];
                $i++;

                continue;
            }

            if ($b['t'] === 'image') {
                $nx = $B[$i + 1] ?? null;
                $fig = $this->fig($b['m'] ?? null, (string) ($b['cap'] ?? ''));
                if ($fig && $nx && $nx['t'] === 'text') {
                    $pieces[] = ['k' => 'spread', 'fig' => $fig, 'text' => (string) $nx['v']];
                    $i += 2;

                    continue;
                }
                if ($fig) {
                    $pieces[] = ['k' => 'figure', 'cls' => 'full', 'fig' => $fig];
                }
                $i++;

                continue;
            }

            if ($b['t'] === 'embed') {
                [$piece, $i] = $this->embeds($B, $i);
                if ($piece) {
                    $pieces[] = $piece;
                }

                continue;
            }

            array_push($pieces, ...$this->piece($b));
            $i++;
        }
        $close();

        return ['short' => false, 'lead' => $lead, 'photos' => [], 'pieces' => $pieces];
    }

    /**
     * The page without chapters: lead + facts, one picture grid, the rest.
     *
     * @param  list<array<string, mixed>>  $B
     */
    private function shortPage(array $B): array
    {
        $firstAt = collect($B)->search(fn ($b) => $b['t'] === 'text');
        $lead = $firstAt === false ? '' : trim((string) $B[$firstAt]['v']);
        $rest = array_values(array_filter($B, fn ($b, $k) => $k !== $firstAt && $b['t'] !== 'credits', ARRAY_FILTER_USE_BOTH));

        $photos = [];
        foreach ($rest as $b) {
            if ($b['t'] === 'image' && ($fig = $this->fig($b['m'] ?? null, (string) ($b['cap'] ?? '')))) {
                $photos[] = $fig;
            }
            if ($b['t'] === 'gallery') {
                foreach ($this->items($b) as $item) {
                    if ($fig = $this->fig($item['m'], (string) ($item['cap'] ?? ''))) {
                        $photos[] = $fig;
                    }
                }
            }
        }

        $others = array_values(array_filter($rest, fn ($b) => ! in_array($b['t'], ['image', 'gallery'], true)));
        $pieces = [];
        for ($j = 0, $n = count($others); $j < $n;) {
            if ($others[$j]['t'] === 'embed') {
                [$piece, $j] = $this->embeds($others, $j);
                if ($piece) {
                    $pieces[] = $piece;
                }

                continue;
            }
            array_push($pieces, ...$this->piece($others[$j]));
            $j++;
        }

        return ['short' => true, 'lead' => $lead, 'photos' => $photos, 'pieces' => $pieces];
    }

    /**
     * One block that needs no neighbours.
     *
     * @return list<array<string, mixed>>
     */
    private function piece(array $b): array
    {
        switch ($b['t']) {
            case 'text':
                return [['k' => 'txt', 'text' => (string) $b['v']]];
            case 'quote':
                return trim((string) ($b['v'] ?? '')) === '' ? [] : [['k' => 'quote', 'text' => (string) $b['v'], 'by' => (string) ($b['by'] ?? '')]];
            case 'image':
                $fig = $this->fig($b['m'] ?? null, (string) ($b['cap'] ?? ''));

                return $fig ? [['k' => 'figure', 'cls' => 'full', 'fig' => $fig]] : [];
            case 'gallery':
                return $this->gallery($b);
            case 'beforeafter':
                $after = $this->media[$b['ma'] ?? 0] ?? $this->media[$b['mb'] ?? 0] ?? null;
                $before = $this->media[$b['mb'] ?? 0] ?? $after;

                return $after ? [['k' => 'ba', 'before' => $before['url'], 'after' => $after['url'],
                    'lb' => (string) ($b['lb'] ?? '') ?: __('Before'), 'la' => (string) ($b['la'] ?? '') ?: __('After'), 'cap' => (string) ($b['cap'] ?? '')]] : [];
            case 'look':
                $items = array_values(array_filter($b['items'] ?? [], fn ($x) => trim((string) ($x['n'] ?? '')) !== ''));
                $pic = $this->media[$b['m'] ?? 0] ?? null;

                return $pic && $items ? [['k' => 'look', 'url' => $pic['url'], 'title' => (string) ($b['title'] ?? ''), 'items' => array_map(
                    fn ($x) => ['n' => $x['n'], 'd' => $this->confidential((string) ($x['d'] ?? '')), 'x' => (int) ($x['x'] ?? 50), 'y' => (int) ($x['y'] ?? 50)],
                    $items,
                )]] : [];
            case 'video':
                $url = (string) ($b['url'] ?? '');

                return $url === '' ? [] : [['k' => 'video', 'url' => $url, 'player' => ProjectBlocks::player($url),
                    'poster' => ProjectBlocks::poster($url) ?? $this->project->lead_image_url, 'cap' => (string) ($b['cap'] ?? ''),
                    'platform' => ProjectBlocks::platform($url)]];
            case 'results':
                $rows = $this->project->resultRows();

                return $rows === [] ? [] : [['k' => 'results', 'rows' => $rows]];
            default:
                return [];
        }
    }

    /**
     * Photos by count: 1 wide · 2 a pair · 3 one wide then a pair · 4+ Cases.
     *
     * @return list<array<string, mixed>>
     */
    private function gallery(array $b): array
    {
        $figs = [];
        foreach ($this->items($b) as $item) {
            if ($fig = $this->fig($item['m'], (string) ($item['cap'] ?? ''))) {
                $figs[] = $fig;
            }
        }

        return match (count($figs)) {
            0 => [],
            1 => [['k' => 'figure', 'cls' => 'full', 'fig' => $figs[0]]],
            2 => [['k' => 'pair', 'figs' => $figs]],
            3 => [['k' => 'figure', 'cls' => 'full', 'fig' => $figs[0]], ['k' => 'pair', 'figs' => [$figs[1], $figs[2]]]],
            default => [$this->casesFrom($figs, null)],
        };
    }

    /**
     * The Cases gallery for a Photos block under a chapter.
     *
     * @param  array{k: string, title: string, intro: string}  $chapter
     */
    private function cases(array $b, array $chapter): array
    {
        $figs = [];
        foreach ($this->items($b) as $item) {
            if ($fig = $this->fig($item['m'], (string) ($item['cap'] ?? ''))) {
                $figs[] = $fig;
            }
        }

        return $this->casesFrom($figs, $chapter);
    }

    /**
     * @param  list<array<string, mixed>>  $figs  already numbered
     * @param  array{k: string, title: string, intro: string}|null  $chapter
     */
    private function casesFrom(array $figs, ?array $chapter): array
    {
        foreach ($figs as $k => $fig) {
            $figs[$k]['cls'] = 'cs-fig '.self::CASE_W[$k % 5];
            $figs[$k]['ar'] = self::CASE_AR[$k % 5];
        }

        return [
            'k' => 'cases',
            'chapter' => $chapter,
            'title' => $chapter['title'] ?? __('Gallery'),
            'start' => $figs ? $figs[0]['no'] : 1,
            'total' => $this->total,
            'figs' => $figs,
        ];
    }

    /**
     * A run of social posts next to each other, as one row.
     *
     * @param  list<array<string, mixed>>  $list
     * @return array{0: array<string, mixed>|null, 1: int}
     */
    private function embeds(array $list, int $i): array
    {
        $posts = [];
        while (isset($list[$i]) && $list[$i]['t'] === 'embed') {
            if (($url = (string) ($list[$i]['url'] ?? '')) !== '') {
                $platform = ProjectBlocks::platform($url) ?: __('Link');
                $who = ProjectBlocks::handle($url);
                $posts[] = ['url' => $url, 'platform' => $platform, 'who' => $who,
                    'initials' => collect(preg_split('/[\s._-]+/', $who))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode(''),
                    'poster' => ProjectBlocks::poster($url) ?? $this->project->lead_image_url];
            }
            $i++;
        }

        return [$posts ? ['k' => 'embeds', 'posts' => $posts] : null, $i];
    }

    /**
     * A numbered picture (or video) for a media id, or null when it is gone.
     */
    private function fig(mixed $id, string $cap): ?array
    {
        $item = $this->media[(int) $id] ?? null;
        if (! $item) {
            return null;
        }

        return ['url' => $item['url'], 'video' => $item['video'], 'mime' => $item['mime'], 'cap' => $cap, 'no' => ++$this->no, 'cls' => '', 'ar' => ''];
    }

    /**
     * A Photos block's items that still point at a picture.
     *
     * @return list<array<string, mixed>>
     */
    private function items(array $b): array
    {
        return array_values(array_filter($b['items'] ?? [], fn ($x) => isset($this->media[(int) ($x['m'] ?? 0)])));
    }

    /**
     * On a confidential project, a look's piece never names the client
     * ("Client's own" instead) — the prototype's NDA rule.
     */
    private function confidential(string $text): string
    {
        $client = trim((string) $this->project->client_name);

        return $this->project->client_confidential && $client !== ''
            ? str_ireplace($client, __('Client’s own'), $text)
            : $text;
    }
}
