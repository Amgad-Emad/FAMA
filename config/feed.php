<?php

/*
|--------------------------------------------------------------------------
| Feed configuration (Phase 5 — docs/specs/feed-spec.md, ADR-F1…F8)
|--------------------------------------------------------------------------
| Every tunable the feed uses lives here — ranking weights, limits, windows.
| No magic numbers in feed code.
*/

return [

    // Minutes after publish/creation during which the author may edit a post
    // or comment (D11). After the window, the body locks.
    'edit_window_minutes' => 15,

    // Talents may follow brands (flipped TRUE in revision 5Z — the brand
    // suggestion rail requires it; brands.followers_count landed with it).
    // Tracks the `brands` release flag so a talent-only V1 (FEATURE_BRANDS=false)
    // also blocks talent→brand follow writes at App\Actions\Feed\ToggleFollow.
    'allow_following_brands' => env('FEATURE_BRANDS', true),

    // Open reports on one reportable that auto-hide it pending review (5Q).
    'auto_hide_reports' => 5,

    // The report-reason taxonomy (5AF) — config-driven, validated in
    // StoreReportRequest; 'other' requires a note. Labels live in lang files.
    'report_reasons' => ['spam', 'nudity', 'harassment', 'hate', 'impersonation', 'ip', 'other'],

    // Link-chip drivers (ADR-F4), first match wins; GenericProvider is always
    // appended last by the registry. Adding a provider = a driver class + one
    // line here.
    'providers' => [
        App\Feed\Links\Providers\InstagramProvider::class,
        App\Feed\Links\Providers\TikTokProvider::class,
        App\Feed\Links\Providers\YouTubeProvider::class,
        App\Feed\Links\Providers\BehanceProvider::class,
        App\Feed\Links\Providers\VimeoProvider::class,
        App\Feed\Links\Providers\XProvider::class,
        App\Feed\Links\Providers\LinkedInProvider::class,
        App\Feed\Links\Providers\PinterestProvider::class,
        App\Feed\Links\Providers\DribbbleProvider::class,
        App\Feed\Links\Providers\SpotifyProvider::class,
        App\Feed\Links\Providers\SoundCloudProvider::class,
        App\Feed\Links\Providers\FacebookProvider::class,
        App\Feed\Links\Providers\SnapchatProvider::class,
    ],

    // Best-effort async enrichment (FetchLinkPreviewJob). The SSRF guard and
    // caps are hard security requirements, not tuning knobs to relax.
    'enrichment' => [
        'timeout_seconds' => 5,
        'max_redirects' => 3,
        'max_bytes' => 512 * 1024,
        'ttl_days' => 30,
        // Providers whose oEmbed endpoint we trust for metadata.
        'oembed' => [
            'youtube' => 'https://www.youtube.com/oembed',
            'vimeo' => 'https://vimeo.com/api/oembed.json',
        ],
        // Providers whose (sanitized, iframe-only) embed_html may be stored.
        'embed_allowlist' => ['youtube', 'vimeo'],
    ],

    // Ranking (Prompt 5I, ADR-F5/D9/D10/D14). EVERY weight and window lives
    // here — no magic numbers in FeedService.
    'ranking' => [
        'per_page' => 20,
        'tier1_days' => 30,          // followed authors, last N days
        'tier2_days' => 30,          // discovery pool window
        'tier2_pool' => 400,         // max discovery candidates scored per session
        'inject_every' => 4,         // one tier-2 item after every N tier-1 items
        'casting_every' => 6,        // every Nth feed position is a casting_strip slot
        'diversity_max_consecutive' => 2,
        'cache_minutes' => 10,       // candidate ID list TTL (per viewer + seed)
        'casting_cache_minutes' => 5,
        'casting_size' => 12,
        'weights' => [
            'affinity' => 3.0,       // shared Skills between post tags and the viewer
            'velocity' => 2.0,       // engagement velocity (log-damped)
            'freshness' => 1.5,      // new-author boost
            'recency' => 2.5,        // exponential publish-age decay
        ],
        'velocity_hours' => 48,
        'fresh_author_days' => 14,
        'recency_half_life_days' => 7,
    ],

    // FeedSeeder scale (Prompt 5F) — the demo dataset the UI prompts are
    // evaluated against. Tests override these downward for speed.
    'seeder' => [
        'talents' => 40,
        'brands' => 15,
        'posts' => 300,
    ],

    // Follow-suggestion rails (revision 5Z). Tier bonuses dominate the
    // high-signal base so the documented ranking order holds; the base score
    // breaks ties inside a tier. No magic numbers in SuggestionService.
    'suggestions' => [
        'cache_minutes' => 5,
        'shown_ttl_days' => 7,       // shown-but-unacted suggestions rest a week
        'shown_penalty' => 10000,    // shown-rest ranks BELOW every fresh candidate,
                                     // but is never removed — a small pool (few brands)
                                     // still fills the rail instead of vanishing
        'pool_per_tier' => 30,
        'signal' => [                // the "high signal" base score
            'completeness' => 2.0,   // avatar + bio (+ verified for brands)
            'recent_posts' => 1.5,   // published posts in the last 30 days
            'followers' => 1.0,      // log-damped follower count
            'age_days' => 0.5,       // account age, log-damped
        ],
        'tiers' => [                 // bonuses per ranking tier
            'second_degree' => 500,
            'mentioned_in_liked' => 400,
            'contract_history' => 400,
            'shared_skill' => 300,
            'network_followed' => 250,
            'same_city' => 200,
        ],
    ],

    // Notifications (feed-spec §9): repeat likes on one target inside this
    // window collapse into one record; a refollow inside it won't re-notify.
    'notifications' => [
        'aggregate_minutes' => 60,
    ],

    // Content limits enforced by validation (feed-spec §4, §7, §8).
    'limits' => [
        'body_max_chars' => 2200,
        'mentions_per_post' => 10,
        'mentions_per_comment' => 5,
        'urls_per_body' => 5,
        'media_per_post' => 10,
        'image_max_kb' => 8 * 1024,
        'video_max_kb' => 100 * 1024,
        'video_max_seconds' => 60,
        'pinned_per_talent' => 1,
    ],

];
