<?php

namespace App\Services;

use App\Models\Talent;
use App\Models\TalentType;
use App\Support\Features;
use Illuminate\Support\Facades\Cache;

/**
 * The AI map (ADR-AO): `/llms.txt` (the llmstxt.org format — Markdown an AI
 * assistant reads to understand the site) and `/robots.txt`. Read-only.
 *
 * llms.txt says what Fama is, how its pages are built (profile → skill tabs →
 * projects with credits), where the sitemap is, and lists the most recently
 * updated public profiles with their skills, so an assistant asked "find a
 * fashion stylist in Cairo" has real, current pages to point at. Cached like
 * the sitemaps.
 */
class LlmsTxtService
{
    /** How many public profiles llms.txt lists (the sitemap has them all). */
    private const PROFILES = 100;

    /**
     * The llms.txt body (Markdown).
     */
    public function llms(): string
    {
        return Cache::remember('seo:llms', now()->addMinutes((int) config('seo.sitemap.cache_minutes', 60)), function (): string {
            $profiles = Talent::query()
                ->where('is_published', true)->whereNotNull('slug')
                ->with(['talentTypes'])
                ->latest('updated_at')
                ->limit(self::PROFILES)
                ->get(['id', 'slug', 'display_name', 'base_city', 'base_country', 'updated_at']);

            return view('seo.llms', [
                'profiles' => $profiles,
                'skills' => TalentType::query()->alphabetical()->get(['id', 'name', 'slug']),
                'discover' => Features::enabled('discover'),
            ])->render();
        });
    }

    /**
     * The robots.txt body. A non-indexable site (staging, local) disallows
     * everything; production keeps crawlers out of the signed-in areas and
     * points them at the sitemap.
     */
    public function robots(): string
    {
        $lines = ['User-agent: *'];

        if (! config('seo.indexable')) {
            $lines[] = 'Disallow: /';

            return implode("\n", $lines)."\n";
        }

        foreach (['talent/', 'admin/', 'brand/', 'notifications', 'dashboard', 'profile', 'api/', 'docs'] as $path) {
            $lines[] = 'Disallow: /'.$path;
            $lines[] = 'Disallow: /ar/'.$path;
        }
        $lines[] = 'Disallow: /*?preview=';
        $lines[] = 'Disallow: /*?visitor=';
        $lines[] = 'Allow: /';
        $lines[] = '';
        $lines[] = 'Sitemap: '.route('seo.sitemap');

        return implode("\n", $lines)."\n";
    }
}
