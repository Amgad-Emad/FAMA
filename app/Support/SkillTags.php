<?php

namespace App\Support;

use App\Models\TalentType;

/**
 * Skill hashtags (fama-ui feed, 2026-10-12): a post can name a skill inline as
 * #CamelCase of its English name ("Hair & makeup" → #HairMakeup). A tag that
 * resolves to a skill links to the feed filtered by it; the composer turns the
 * author's OWN tagged skills into the post's skill_ids (ADR-F1 keeps skills
 * own-only). The catalog is read at most once per request.
 */
class SkillTags
{
    /**
     * The hashtag for a skill name: every alphanumeric word, capitalised, joined.
     */
    public static function tag(string $name): string
    {
        $words = preg_split('/[^A-Za-z0-9]+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return implode('', array_map(fn (string $word) => ucfirst($word), $words));
    }

    /**
     * lower(tag) => ['slug' => …, 'tag' => …] for every skill, memoised per request.
     *
     * @return array<string, array{slug: string, tag: string}>
     */
    public static function map(): array
    {
        return once(fn () => TalentType::query()->get(['id', 'name', 'slug'])
            ->mapWithKeys(function (TalentType $type): array {
                $tag = self::tag((string) $type->getTranslation('name', 'en'));

                return [mb_strtolower($tag) => ['slug' => $type->slug, 'tag' => $tag]];
            })
            ->all());
    }

    /**
     * Link every #Tag in already-escaped HTML that names a skill. The lookbehind
     * keeps HTML entities (&#039;) and mid-word hashes untouched; the catalog is
     * only queried when the text holds a hash at all.
     */
    public static function linkify(string $html): string
    {
        if (! str_contains($html, '#')) {
            return $html;
        }

        $map = self::map();

        return (string) preg_replace_callback('/(?<![&\w\/])#([A-Za-z][A-Za-z0-9]*)/', function (array $m) use ($map): string {
            $hit = $map[mb_strtolower($m[1])] ?? null;

            if ($hit === null) {
                return $m[0];
            }

            return '<a class="mention" href="'.e(route('feed', ['skill' => $hit['slug']])).'">#'.e($hit['tag']).'</a>';
        }, $html);
    }

    /**
     * Whether a raw body names at least one skill by hashtag (the card then
     * skips the repeated skill tags under the text, as the prototype does).
     */
    public static function bodyHasTag(?string $body): bool
    {
        if ($body === null || ! str_contains($body, '#')) {
            return false;
        }

        preg_match_all('/(?<![&\w\/])#([A-Za-z][A-Za-z0-9]*)/', $body, $matches);
        $map = self::map();

        foreach ($matches[1] as $tag) {
            if (isset($map[mb_strtolower($tag)])) {
                return true;
            }
        }

        return false;
    }
}
