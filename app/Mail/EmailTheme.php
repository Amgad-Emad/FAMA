<?php

namespace App\Mail;

/**
 * The Fama brand, flattened for email. Mail clients can't read the app's CSS
 * variables (resources/css/app.css), so every email component pulls its hex
 * values and font stacks from HERE — one place to restyle all mail. Values
 * mirror the official branding tokens: warm cream ground, plum ink, Orchid
 * Purple accent, the logo's yellow star.
 */
final class EmailTheme
{
    /**
     * Light palette (the default render). Keys match the app's token names.
     */
    public const COLORS = [
        'bg' => '#FAF6E0',
        'surface' => '#FDFBEF',
        'card' => '#FFFFFF',
        'line' => '#EDE6CB',
        'line_strong' => '#D8CEAC',
        'ink' => '#231A25',
        'muted' => '#695E6D',
        'subtle' => '#98909D',
        'accent' => '#AD50D3',
        'accent_weak' => '#F7E0FF',
        'accent_ink' => '#8B2FA8',
        'on_accent' => '#FFFFFF',
        'star' => '#FFE72F',
        'gold' => '#7E6600',
        'gold_weak' => '#FFF6C2',
        'success' => '#0B7059',
        'success_weak' => '#DDF8EF',
        'danger' => '#B14A3A',
        'danger_weak' => '#F6E6E0',
    ];

    /**
     * Font stacks. Web fonts load where the client allows (Apple Mail, iOS);
     * everywhere else the system fallbacks keep the same proportions.
     */
    public const FONTS = [
        'display' => "'Bricolage Grotesque', 'Helvetica Neue', Helvetica, Arial, sans-serif",
        'body' => "'Sora', 'Helvetica Neue', Helvetica, Arial, sans-serif",
        'mono' => "'IBM Plex Mono', SFMono-Regular, Menlo, Consolas, monospace",
        'arabic' => "'IBM Plex Sans Arabic', Tahoma, Arial, sans-serif",
    ];

    /**
     * A palette colour by token name.
     */
    public static function color(string $key): string
    {
        return self::COLORS[$key];
    }

    /**
     * A font stack by role. In Arabic every role uses the Arabic stack, since
     * the Latin display/body faces have no Arabic glyphs.
     */
    public static function font(string $role): string
    {
        return self::isRtl() && $role !== 'mono' ? self::FONTS['arabic'] : self::FONTS[$role];
    }

    /**
     * Whether the email is rendering in a right-to-left locale. Queued
     * mailables render in their own locale, so this reads the app locale at
     * render time rather than the request.
     */
    public static function isRtl(): bool
    {
        return app()->getLocale() === 'ar';
    }

    /**
     * The HTML `dir` value for the current locale.
     */
    public static function dir(): string
    {
        return self::isRtl() ? 'rtl' : 'ltr';
    }

    /**
     * The physical "start" side for align/text-align attributes, which have no
     * logical equivalents in email HTML.
     */
    public static function start(): string
    {
        return self::isRtl() ? 'right' : 'left';
    }

    /**
     * The physical "end" side.
     */
    public static function end(): string
    {
        return self::isRtl() ? 'left' : 'right';
    }

    /**
     * The address shown in the footer for questions — the support profile's
     * From (config/mail.php "senders"), or null when not configured.
     */
    public static function supportAddress(): ?string
    {
        return config('mail.senders.support.from.address') ?: null;
    }
}
