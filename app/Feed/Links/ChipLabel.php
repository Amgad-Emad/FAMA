<?php

namespace App\Feed\Links;

use App\Models\LinkPreview;

/**
 * The chip label rules (Prompt 5V): profiles show @handle; content shows the
 * enriched title when it landed, else "{Platform} {noun}"; anything else the
 * registrable domain. A raw URL is never the label.
 */
class ChipLabel
{
    /**
     * Content-noun per provider (used when a resource id parsed but no
     * enriched title exists yet — or ever).
     *
     * @var array<string, string>
     */
    private const NOUNS = [
        'instagram' => 'Instagram post',
        'tiktok' => 'TikTok video',
        'youtube' => 'YouTube video',
        'behance' => 'Behance project',
        'vimeo' => 'Vimeo video',
        'x' => 'X post',
        'linkedin' => 'LinkedIn post',
        'pinterest' => 'Pinterest pin',
        'dribbble' => 'Dribbble shot',
        'spotify' => 'Spotify track',
        'soundcloud' => 'SoundCloud track',
        'facebook' => 'Facebook post',
        'snapchat' => 'Snapchat story',
    ];

    /**
     * The visible chip label for a preview row.
     */
    public static function for(LinkPreview $preview): string
    {
        // A parsed resource (post/video/gallery/…) — enriched title wins.
        if ($preview->provider_resource_id !== null) {
            if ($preview->status === 'ok' && filled($preview->title)) {
                return mb_substr((string) $preview->title, 0, 60);
            }

            return self::NOUNS[$preview->provider] ?? self::domain($preview);
        }

        // A profile — the handle.
        if ($preview->provider_handle !== null) {
            return '@'.$preview->provider_handle;
        }

        // Generic / unparsed: enriched title, else the registrable domain.
        if ($preview->status === 'ok' && filled($preview->title)) {
            return mb_substr((string) $preview->title, 0, 60);
        }

        return self::domain($preview);
    }

    /**
     * The registrable-domain fallback label.
     */
    private static function domain(LinkPreview $preview): string
    {
        $host = strtolower((string) parse_url($preview->url, PHP_URL_HOST));

        return preg_replace('/^(www|m|mobile)\./', '', $host) ?: $preview->provider;
    }
}
