<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Talent;
use App\Support\Features;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * The XML sitemaps (ADR-AO) — read-only, so no transactions.
 *
 *  /sitemap.xml                  the index: one entry per file below
 *  /sitemaps/pages.xml           the public entry points (feed, join, discovery when on)
 *  /sitemaps/profiles-{n}.xml    every PUBLIC profile (is_published — the same rule the
 *                                profile page uses; private, suspended and deleted are left out)
 *  /sitemaps/projects-{n}.xml    every project of a public profile (the project page's rule)
 *
 * Each file holds `seo.sitemap.per_page` URLs and is cached for
 * `seo.sitemap.cache_minutes` (plain strings — never Eloquent models in the cache).
 * URLs are the default-locale ones: Arabic copy is paused, and the canonical tag
 * points there too.
 */
class SitemapService
{
    /**
     * The sitemap index (XML).
     */
    public function index(): string
    {
        return $this->remember('index', function (): string {
            $files = [['loc' => route('seo.sitemap.file', ['file' => 'pages']), 'lastmod' => null]];

            foreach (['profiles' => $this->profiles(), 'projects' => $this->projects()] as $kind => $query) {
                $pages = max(1, (int) ceil($query->count() / $this->perPage()));
                for ($n = 1; $n <= $pages; $n++) {
                    $files[] = ['loc' => route('seo.sitemap.file', ['file' => "{$kind}-{$n}"]), 'lastmod' => null];
                }
            }

            return view('seo.sitemap-index', ['files' => $files])->render();
        });
    }

    /**
     * One sitemap file by name (`pages`, `profiles-{n}`, `projects-{n}`), or
     * null when there is no such file.
     */
    public function file(string $file): ?string
    {
        if ($file === 'pages') {
            return $this->remember($file, fn () => $this->urlset($this->pageUrls()));
        }
        if (! preg_match('/^(profiles|projects)-([1-9]\d{0,5})$/', $file, $m)) {
            return null;
        }
        [$kind, $page] = [$m[1], (int) $m[2]];
        $total = ($kind === 'profiles' ? $this->profiles() : $this->projects())->count();
        if ($page > max(1, (int) ceil($total / $this->perPage()))) {
            return null;
        }

        return $this->remember($file, fn () => $this->urlset(
            $kind === 'profiles' ? $this->profileUrls($page) : $this->projectUrls($page),
        ));
    }

    /**
     * The public entry points. Discovery pages only while their feature is on.
     *
     * @return list<array{loc: string, lastmod: ?string, priority: string}>
     */
    private function pageUrls(): array
    {
        $urls = [
            ['loc' => route('feed'), 'lastmod' => null, 'priority' => '0.8'],
            ['loc' => route('register'), 'lastmod' => null, 'priority' => '0.5'],
        ];
        if (Features::enabled('discover')) {
            $urls[] = ['loc' => route('discover'), 'lastmod' => null, 'priority' => '0.7'];
        }
        if (Features::enabled('brands')) {
            $urls[] = ['loc' => route('brands.discover'), 'lastmod' => null, 'priority' => '0.5'];
        }

        return $urls;
    }

    /**
     * One page of public profiles.
     *
     * @return list<array{loc: string, lastmod: ?string, priority: string}>
     */
    private function profileUrls(int $page): array
    {
        return $this->profiles()
            ->orderBy('id')
            ->forPage($page, $this->perPage())
            ->get(['id', 'slug', 'updated_at'])
            ->map(fn (Talent $t) => ['loc' => route('talent.public', ['slug' => $t->slug]), 'lastmod' => $t->updated_at?->toAtomString(), 'priority' => '0.9'])
            ->all();
    }

    /**
     * One page of projects that belong to a public profile.
     *
     * @return list<array{loc: string, lastmod: ?string, priority: string}>
     */
    private function projectUrls(int $page): array
    {
        return $this->projects()
            ->orderBy('projects.id')
            ->forPage($page, $this->perPage())
            ->get(['projects.id', 'projects.updated_at', 'talents.slug as owner_slug'])
            ->map(fn (Project $p) => ['loc' => route('talent.work', ['slug' => $p->owner_slug, 'project' => $p->id]), 'lastmod' => $p->updated_at?->toAtomString(), 'priority' => '0.8'])
            ->all();
    }

    /**
     * Public profiles: published and not deleted (SoftDeletes scopes the rest out).
     */
    private function profiles(): Builder
    {
        return Talent::query()->where('is_published', true)->whereNotNull('slug');
    }

    /**
     * Projects whose owner's profile is public.
     */
    private function projects(): Builder
    {
        return Project::query()
            ->join('talents', 'talents.id', '=', 'projects.talent_id')
            ->where('talents.is_published', true)
            ->whereNull('talents.deleted_at');
    }

    /**
     * A <urlset> document for a list of URLs.
     *
     * @param  list<array{loc: string, lastmod: ?string, priority: string}>  $urls
     */
    private function urlset(array $urls): string
    {
        return view('seo.sitemap', ['urls' => $urls])->render();
    }

    /**
     * Cache one generated file.
     */
    private function remember(string $key, \Closure $build): string
    {
        return Cache::remember('seo:sitemap:'.$key, now()->addMinutes((int) config('seo.sitemap.cache_minutes', 60)), $build);
    }

    private function perPage(): int
    {
        return max(1, (int) config('seo.sitemap.per_page', 5000));
    }
}
