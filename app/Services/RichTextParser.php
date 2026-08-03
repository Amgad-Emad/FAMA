<?php

namespace App\Services;

use App\Data\Feed\MentionData;
use App\Data\Feed\ParsedBodyData;
use App\Data\Feed\UrlData;
use App\Models\Brand;
use App\Models\Talent;

/**
 * Turns a raw post/comment body into structured mentions and URLs
 * (feed-spec §7–§8, ADR-F3/F4).
 *
 * Offsets are CHARACTER offsets computed multibyte-correctly — an Arabic body
 * with a Latin @username must address the mention where it actually sits.
 * Usernames resolve against talents.slug first, then brands.slug; an
 * unresolved @token stays plain text (no mention row). URLs are http/https
 * only, trailing punctuation trimmed. The parser never throws — limits are
 * enforced by the Sync actions that persist the result.
 */
class RichTextParser
{
    /**
     * An @username token: the @ must not ride on a preceding word character
     * (so e-mail addresses never produce mentions). Usernames are slugs —
     * ASCII letters, digits, hyphens.
     */
    private const MENTION_PATTERN = '/(?<![\p{L}\p{N}_@])@([A-Za-z0-9][A-Za-z0-9\-]*)/u';

    /**
     * An http/https URL up to whitespace or closing angle/quote.
     */
    private const URL_PATTERN = '~https?://[^\s<>"\']+~u';

    /**
     * Punctuation that ends a sentence, not a URL.
     */
    private const URL_TRAILING = '.,;:!?)…»”\'"';

    /**
     * Parse the raw body into mentions (resolved against current Usernames)
     * and URLs, with multibyte-correct character offsets.
     */
    public function parse(string $raw): ParsedBodyData
    {
        return new ParsedBodyData(
            raw: $raw,
            mentions: $this->extractMentions($raw),
            urls: $this->extractUrls($raw),
        );
    }

    /**
     * Extract and resolve @username tokens.
     *
     * @return array<int, MentionData>
     */
    private function extractMentions(string $raw): array
    {
        if (preg_match_all(self::MENTION_PATTERN, $raw, $matches, PREG_OFFSET_CAPTURE) === false
            || $matches[0] === []) {
            return [];
        }

        $usernames = array_unique(array_map(fn (array $m): string => $m[0], $matches[1]));
        $resolved = $this->resolveUsernames($usernames);

        $mentions = [];

        foreach ($matches[0] as $index => [$token, $byteOffset]) {
            $username = $matches[1][$index][0];

            if (! isset($resolved[$username])) {
                continue; // Unresolved @token stays plain text.
            }

            [$type, $id] = $resolved[$username];

            $mentions[] = new MentionData(
                mentionedType: $type,
                mentionedId: $id,
                username: $username,
                startOffset: $this->charOffset($raw, $byteOffset),
                length: mb_strlen($token),
            );
        }

        return $mentions;
    }

    /**
     * Extract http/https URLs, trimming trailing sentence punctuation.
     *
     * @return array<int, UrlData>
     */
    private function extractUrls(string $raw): array
    {
        if (preg_match_all(self::URL_PATTERN, $raw, $matches, PREG_OFFSET_CAPTURE) === false) {
            return [];
        }

        $urls = [];

        foreach ($matches[0] as [$url, $byteOffset]) {
            $trimmed = rtrim($url, self::URL_TRAILING);

            if ($trimmed === '' || ! str_contains($trimmed, '://')) {
                continue;
            }

            $urls[] = new UrlData(
                url: $trimmed,
                startOffset: $this->charOffset($raw, $byteOffset),
                length: mb_strlen($trimmed),
            );
        }

        return $urls;
    }

    /**
     * Resolve usernames against talents.slug first, then brands.slug
     * (feed-spec §7) — two whereIn queries total, no per-token lookups.
     *
     * @param  array<int, string>  $usernames
     * @return array<string, array{string, int}> username → [morph alias, id]
     */
    private function resolveUsernames(array $usernames): array
    {
        if ($usernames === []) {
            return [];
        }

        $resolved = [];

        foreach (Talent::query()->whereIn('slug', $usernames)->pluck('id', 'slug') as $slug => $id) {
            $resolved[$slug] = ['talent', (int) $id];
        }

        $remaining = array_diff($usernames, array_keys($resolved));

        if ($remaining !== []) {
            foreach (Brand::query()->whereIn('slug', $remaining)->pluck('id', 'slug') as $slug => $id) {
                $resolved[$slug] = ['brand', (int) $id];
            }
        }

        return $resolved;
    }

    /**
     * Convert a preg byte offset into a character offset — the multibyte
     * correctness the Arabic path depends on.
     */
    private function charOffset(string $raw, int $byteOffset): int
    {
        return mb_strlen(substr($raw, 0, $byteOffset));
    }
}
