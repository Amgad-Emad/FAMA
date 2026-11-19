<?php

/*
|--------------------------------------------------------------------------
| SEO (ADR-AO)
|--------------------------------------------------------------------------
| Search + share + AI-crawler settings. App\Support\Seo reads this for every
| page's <head>; App\Services\SitemapService for /sitemap.xml; the
| SeoController for /robots.txt and /llms.txt.
*/

return [

    // Whether search engines may index the site at all. Off everywhere but
    // production unless set: a staging copy must never compete with the real
    // site in search. When off, robots.txt disallows everything and every
    // page says noindex.
    'indexable' => (bool) env('SEO_INDEXABLE', env('APP_ENV') === 'production'),

    'site_name' => env('SEO_SITE_NAME', 'Fama'),

    // The fallback description (pages with their own text replace it).
    'description' => 'Fama is where creative talent gets seen: photographers, models, stylists, directors, makeup artists and more show their projects, credit the people they made them with, and get found.',

    // The share picture used when a page has none of its own (public path).
    'default_image' => 'fama-ui/img/banners/feed.jpg',

    // Social accounts of Fama itself, for the Organization structured data.
    'same_as' => array_values(array_filter(explode(',', (string) env('SEO_SAME_AS', '')))),

    // Route names a search engine may index. EVERYTHING else is noindex —
    // an allowlist, so a new signed-in page can never leak into search.
    'indexable_routes' => [
        'home', 'feed', 'talent.public', 'talent.work', 'posts.show',
        'login', 'register',
        'discover', 'projects.browse', 'brands.discover', 'brand.public', 'brand.project.public',
    ],

    // Query flags that mark a private variant of a public page.
    'private_query' => ['preview', 'visitor'],

    'sitemap' => [
        // URLs per sitemap file (the protocol allows 50,000; smaller files stay fast).
        'per_page' => 5000,
        // How long a generated sitemap file is cached, in minutes.
        'cache_minutes' => 60,
    ],
];
