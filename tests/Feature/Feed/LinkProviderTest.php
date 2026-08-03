<?php

use App\Feed\Links\LinkProviderRegistry;
use App\Feed\Links\Providers\GenericProvider;
use App\Feed\Links\Providers\InstagramProvider;

// ----- Driver parsing (ADR-F4 — zero network, string only) -------------------

it('parses provider urls into handle/resource from the string alone', function (string $url, string $provider, ?string $handle, ?string $resource) {
    $parsed = app(LinkProviderRegistry::class)->parse($url);

    expect($parsed->provider)->toBe($provider)
        ->and($parsed->providerHandle)->toBe($handle)
        ->and($parsed->providerResourceId)->toBe($resource);
})->with([
    // Instagram: profile, mobile subdomain, post, reel + query, story
    'instagram profile' => ['https://www.instagram.com/amgad/', 'instagram', 'amgad', null],
    'instagram mobile' => ['https://m.instagram.com/amgad', 'instagram', 'amgad', null],
    'instagram post' => ['https://instagram.com/p/Cxyz123/', 'instagram', null, 'Cxyz123'],
    'instagram reel + query' => ['https://instagram.com/reel/Rabc99/?igsh=track', 'instagram', null, 'Rabc99'],
    'instagram story' => ['https://instagram.com/stories/amgad/3216549870/', 'instagram', 'amgad', '3216549870'],

    // TikTok: profile, video + query, short link
    'tiktok profile' => ['https://www.tiktok.com/@rana', 'tiktok', 'rana', null],
    'tiktok video' => ['https://www.tiktok.com/@rana/video/7234567890?lang=en', 'tiktok', 'rana', '7234567890'],
    'tiktok short' => ['https://vm.tiktok.com/ZM8abcDef/', 'tiktok', null, 'ZM8abcDef'],

    // YouTube: watch, short-domain, handle, shorts on mobile
    'youtube watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'youtube', null, 'dQw4w9WgXcQ'],
    'youtube short domain' => ['https://youtu.be/dQw4w9WgXcQ?t=10', 'youtube', null, 'dQw4w9WgXcQ'],
    'youtube handle' => ['https://www.youtube.com/@famastudio', 'youtube', 'famastudio', null],
    'youtube shorts mobile' => ['https://m.youtube.com/shorts/abc123/', 'youtube', null, 'abc123'],

    // Behance: profile trailing slash, gallery + tracking query
    'behance profile' => ['https://www.behance.net/amgad/', 'behance', 'amgad', null],
    'behance gallery' => ['https://www.behance.net/gallery/123456/Retro-Editorial?tracking_source=x', 'behance', null, '123456'],

    // Vimeo: video id, channel-less profile
    'vimeo video' => ['https://vimeo.com/76979871', 'vimeo', null, '76979871'],
    'vimeo profile' => ['https://vimeo.com/famastudio', 'vimeo', 'famastudio', null],

    // X / Twitter: profile on both hosts, status
    'x profile' => ['https://x.com/rana', 'x', 'rana', null],
    'twitter status' => ['https://twitter.com/rana/status/9876543210?s=20', 'x', 'rana', '9876543210'],

    // LinkedIn: person, company
    'linkedin person' => ['https://www.linkedin.com/in/rana-kamel/', 'linkedin', 'rana-kamel', null],
    'linkedin company' => ['https://linkedin.com/company/fama', 'linkedin', 'fama', null],

    // Pinterest: pin, profile
    'pinterest pin' => ['https://www.pinterest.com/pin/987654321/', 'pinterest', null, '987654321'],
    'pinterest profile' => ['https://pinterest.com/ranakamel', 'pinterest', 'ranakamel', null],

    // Dribbble: shot, profile
    'dribbble shot' => ['https://dribbble.com/shots/123-lookbook-cover', 'dribbble', null, '123-lookbook-cover'],
    'dribbble profile' => ['https://dribbble.com/rana', 'dribbble', 'rana', null],

    // Spotify: track + query
    'spotify track' => ['https://open.spotify.com/track/4uLU6hMCjMI75M1A2tKUQC?si=abc', 'spotify', null, '4uLU6hMCjMI75M1A2tKUQC'],

    // SoundCloud: profile, track
    'soundcloud profile' => ['https://soundcloud.com/rana', 'soundcloud', 'rana', null],
    'soundcloud track' => ['https://soundcloud.com/rana/cairo-nights', 'soundcloud', 'rana', 'cairo-nights'],

    // Facebook: numeric profile, page, watch
    'facebook numeric profile' => ['https://www.facebook.com/profile.php?id=100012345', 'facebook', null, '100012345'],
    'facebook page' => ['https://facebook.com/fama.brand', 'facebook', 'fama.brand', null],
    'facebook watch' => ['https://www.facebook.com/watch?v=555000111', 'facebook', null, '555000111'],

    // Snapchat: add, at-handle
    'snapchat add' => ['https://www.snapchat.com/add/rana', 'snapchat', 'rana', null],
    'snapchat handle' => ['https://snapchat.com/@rana', 'snapchat', 'rana', null],

    // Generic fallback
    'generic site' => ['https://studio-portfolio.example.co.uk/work/editorials', 'generic', null, null],
]);

it('resolves drivers in config order with generic always last', function () {
    $registry = app(LinkProviderRegistry::class);

    expect($registry->resolve('https://instagram.com/amgad'))->toBeInstanceOf(InstagramProvider::class)
        ->and($registry->resolve('https://totally-unknown.example'))->toBeInstanceOf(GenericProvider::class);
});
