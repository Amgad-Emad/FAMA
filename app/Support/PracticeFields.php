<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * "About your practice" — each skill's custom fields (ADR-AG), ported from the
 * prototype's assets/js/fields.js (27 fields; its comment says "across 30 skills" but its map
 * covers 31 — ten skills have none. A field describes the practice itself: true of every job").
 *
 * THE home for the field catalog: the editor gets `definitions()` +
 * `bySkill()` as JSON, the server validates with `clean()`, and the public
 * skill tab renders with `facts()`. Values live ONCE per profile
 * (talents.practice, keyed by field id), so a field two skills share — Dialects
 * for Acting and Voice — is filled in once.
 *
 * Two fields are views of data the app already keeps, so nothing is stored
 * twice: `digitals` reads the talent's Digitals content (dated photos), and
 * `software` reads and writes the Software list (software_stack).
 *
 * Value shapes by type:
 *   num {int} · nums {chest,waist,hips,inseam} · sizes {r,c,s} · one string ·
 *   many [string] · two {a:[],b:[]} · swatch [1..10] · range {min,max} ·
 *   levels {name: level} · reach {platform: range} · pairs [[from,to]] ·
 *   licence {cert,reg,exp} · digitals (derived)
 */
class PracticeFields
{
    public const COVER = ['Fully covered', 'With hijab', 'Without hijab on camera', 'Short sleeves', 'Sleeveless', 'Above the knee', 'Fitted cuts', 'Swimwear', 'Underwear'];

    public const TERMS = ['All-female crew', 'No physical contact with the opposite sex', 'No alcohol or tobacco', 'A chaperone present', 'Closed set'];

    public const DIALECTS = ['Modern Standard Arabic', 'Egyptian', 'Levantine', 'Gulf (Najdi)', 'Gulf (Hijazi)', 'Iraqi', 'Maghrebi', 'Sudanese', 'Yemeni'];

    public const LEVELS = ['Native', 'Fluent', 'Good'];

    public const SOFTWARE = ['DaVinci Resolve', 'Premiere Pro', 'Avid', 'After Effects', 'Nuke', 'Flame', 'Pro Tools', 'Capture One', 'Blender', 'Houdini', 'Cinema 4D', 'Maya'];

    public const REGISTER = ['Formal', 'Conversational', 'Advertising', 'Youthful', 'Literary'];

    public const PLATFORMS = ['Instagram', 'TikTok', 'YouTube', 'Snapchat', 'X', 'LinkedIn', 'Podcasts'];

    public const REACH = ['Under 10K', '10K–50K', '50K–100K', '100K–500K', '500K–1M', 'Over 1M'];

    public const LANGS = ['Arabic', 'English', 'French', 'Spanish', 'German', 'Italian', 'Turkish', 'Urdu', 'Hindi', 'Persian', 'Russian', 'Portuguese', 'Chinese', 'Japanese', 'Korean'];

    /** Monk Skin Tone scale, 10 shades. */
    public const MONK = ['#f6ede4', '#f3e7db', '#f7ead0', '#eadaba', '#d7bd96', '#a07e56', '#825c43', '#604134', '#3a312a', '#292420'];

    public const DIGI = ['Face, front', 'Face, side', 'Full length, front', 'Full length, side'];

    /** Digitals older than this many days are flagged (six months). */
    public const DIGITALS_STALE_DAYS = 182;

    /**
     * id => [name, type, options, help].
     *
     * @return array<string, array{0: string, 1: string, 2: mixed, 3: string}>
     */
    public static function definitions(): array
    {
        return [
            'height' => ['Height', 'num', ['unit' => 'cm', 'min' => 120, 'max' => 220], 'The first thing any casting filters on.'],
            'measure' => ['Measurements', 'nums', [['chest', 'Chest'], ['waist', 'Waist'], ['hips', 'Hips'], ['inseam', 'Inside leg', 1]], 'In centimetres. Sample sizes are fixed, so brands filter on these.'],
            'sizes' => ['Clothing and shoe size', 'sizes', ['EU', 'UK', 'US'], 'So the stylist can pull the rail before the fitting.'],
            'digitals' => ['Digitals', 'digitals', self::DIGI, 'Plain background, no retouching, daylight. Dated when you upload, and flagged after six months.'],
            'comfort' => ['Comfort and coverage', 'two', [['Coverage', self::COVER], ['Conditions', self::TERMS]], 'Brands only offer work inside what you choose here, so nobody is asked on set.'],
            'sourcing' => ['Sourcing access', 'many', ['Brand showrooms', 'Multi-brand boutiques', 'Regional distributors', 'Direct with designers', 'Rental archives', 'Private archive'], 'Where you can pull pieces from.'],
            'kit' => ['Your kit', 'one', ['Full professional kit', 'Partial kit, details on request', 'Production provides the kit'], ''],
            'skintone' => ['Skin tone range', 'swatch', self::MONK, 'The Monk scale, 10 shades. Choose every shade you work on confidently.'],
            'hairtypes' => ['Hair type range', 'many', ['Straight', 'Wavy', 'Curly', 'Coily', 'Locs', 'Protective styles', 'Wigs and extensions'], ''],
            'arabic' => ['Working in Arabic', 'one', ['Designs in Arabic and Latin', 'Latin only', 'Arabic only', 'Typesets Arabic, doesn’t design it'], ''],
            'scripts' => ['Scripts', 'many', ['Arabic: Naskh', 'Arabic: Kufi', 'Arabic: Thuluth', 'Arabic: Diwani', 'Arabic: Ruq’ah', 'Latin', 'Arabic and Latin together'], ''],
            'ownop' => ['Owner-operator or hired kit', 'one', ['Owns and operates', 'Owns part of the kit', 'Operates hired kit only'], ''],
            'software' => ['Software', 'many', self::SOFTWARE, ''],
            'networks' => ['Talent networks', 'many', ['Agency-represented talent', 'Street casting', 'Specialist communities', 'Children and families', 'Specific nationalities', 'Sport and specialist skills'], 'The talent you can reach.'],
            'agerange' => ['Playing age', 'range', ['min' => 5, 'max' => 90, 'span' => 5], 'At least five years wide. Separate from your real age.'],
            'dialects' => ['Dialects', 'levels', [self::DIALECTS, self::LEVELS], 'Choose a level for each one you speak.'],
            'booth' => ['Recording booth', 'one', ['Treated booth', 'Treated room', 'Portable booth', 'Untreated'], ''],
            'direct' => ['Live direction', 'one', ['Source-Connect', 'ipDTL', 'Zoom or Teams', 'Phone patch', 'Not available'], 'How a client can direct a session remotely.'],
            'register' => ['Register', 'many', self::REGISTER, ''],
            'platforms' => ['Platforms', 'many', self::PLATFORMS, ''],
            'reach' => ['Reach on each platform', 'reach', self::REACH, 'Ranges, not exact numbers. Verified by connecting the platform where we can.'],
            'langpairs' => ['Language pairs', 'pairs', self::LANGS, 'From the source language to the target.'],
            'expertise' => ['Specialist areas', 'many', ['Legal', 'Medical', 'Financial', 'Technical', 'Marketing and creative'], ''],
            'build' => ['Build in-house', 'one', ['Designs and builds', 'Designs, works with a build partner', 'Designs only, the client builds'], ''],
            'licence' => ['Operating licence', 'licence', null, 'Your remote pilot certificate from GACA and the drone’s registration.'],
            'permits' => ['Permit experience', 'many', ['GCAM filming permit', 'GACA airspace permit', 'Municipal and site permits', 'Heritage sites and AlUla', 'Airports and transport hubs', 'Government buildings', 'Private property'], ''],
            'special' => ['Special skills', 'many', ['Driving', 'Horse riding', 'Swimming', 'Combat', 'Musical instruments', 'Sports', 'Languages'], ''],
        ];
    }

    /**
     * Skill slug => [[field id, optional]] in the spec's order.
     *
     * @return array<string, list<array{0: string, 1: bool}>>
     */
    public static function bySkill(): array
    {
        $sw = [['software', false]];
        $oo = [['ownop', false]];
        $req = fn (string ...$ids) => array_map(fn ($id) => [$id, false], $ids);

        return [
            'modeling' => $req('height', 'measure', 'sizes', 'digitals', 'comfort'),
            'styling' => $req('sourcing'),
            'costume-wardrobe' => $req('sourcing'),
            'makeup' => $req('kit', 'skintone'),
            'hair' => $req('kit', 'hairtypes'),
            'graphic-design' => $req('arabic'),
            'product-interface-design' => $req('arabic'),
            'type-lettering' => $req('scripts'),
            'cinematography' => $oo, 'camera-department' => $oo, 'lighting-grip' => $oo, 'production-sound' => $oo,
            'retouching-post' => $sw, 'editing' => $sw, 'motion-design' => $sw, 'animation' => $sw, '3d-cgi' => $sw,
            'colour-grading' => $sw, 'vfx' => $sw, 'sound-post' => $sw,
            'casting' => $req('networks'),
            'acting' => array_merge($req('agerange', 'dialects', 'comfort'), [['special', true]]),
            'voice' => array_merge($req('dialects', 'booth', 'direct'), [['register', true]]),
            'content-creation' => $req('platforms', 'reach', 'dialects'),
            'presenting-hosting' => array_merge($req('dialects'), [['agerange', true]]),
            'writing' => $req('langpairs', 'register'),
            'translation-localisation' => array_merge($req('langpairs', 'dialects'), [['expertise', true]]),
            'spatial-set-design' => $req('build'),
            'dance-movement' => $req('comfort'),
            'aerial-drone' => $req('licence'),
            'location' => $req('permits'),
        ];
    }

    /**
     * The fields one skill asks for.
     *
     * @return list<array{id: string, opt: bool}>
     */
    public static function forSkill(string $slug): array
    {
        return array_map(fn ($f) => ['id' => $f[0], 'opt' => $f[1]], self::bySkill()[$slug] ?? []);
    }

    /**
     * Field ids stored in talents.practice (the two derived ones are not).
     *
     * @return list<string>
     */
    public static function storedIds(): array
    {
        return array_values(array_diff(array_keys(self::definitions()), ['digitals', 'software']));
    }

    /**
     * Keep only well-formed values of known stored fields; anything empty is
     * dropped (an emptied field leaves the profile). Never throws: a malformed
     * value is simply not kept.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function clean(array $input): array
    {
        $defs = self::definitions();
        $out = [];

        foreach (self::storedIds() as $id) {
            if (! array_key_exists($id, $input)) {
                continue;
            }
            [, $type, $opts] = $defs[$id];
            $value = self::cleanValue($type, $opts, $input[$id]);
            if (self::filled($id, $value)) {
                $out[$id] = $value;
            }
        }

        return $out;
    }

    /**
     * Normalise one value to its type's shape.
     */
    private static function cleanValue(string $type, mixed $opts, mixed $v): mixed
    {
        $list = fn ($x) => is_array($x) ? array_values($x) : [];
        $pick = fn ($x, array $allowed) => array_values(array_unique(array_filter($list($x), fn ($o) => is_string($o) && in_array($o, $allowed, true))));
        $int = fn ($x, int $min, int $max) => is_numeric($x) && (int) $x >= $min && (int) $x <= $max ? (int) $x : null;
        $str = fn ($x, int $max) => is_scalar($x) ? mb_substr(trim((string) $x), 0, $max) : '';

        return match ($type) {
            'num' => $int($v, $opts['min'], $opts['max']),
            'nums' => array_filter(
                collect($opts)->mapWithKeys(fn ($p) => [$p[0] => $int(is_array($v) ? ($v[$p[0]] ?? null) : null, 20, 250)])->all(),
                fn ($x) => $x !== null,
            ),
            'sizes' => is_array($v) ? array_filter([
                'r' => in_array($v['r'] ?? null, $opts, true) ? $v['r'] : 'EU',
                'c' => $str($v['c'] ?? '', 8),
                's' => $str($v['s'] ?? '', 6),
            ], fn ($x) => $x !== '') : [],
            'one' => is_string($v) && in_array($v, $opts, true) ? $v : '',
            'many' => $pick($v, $opts),
            'two' => is_array($v) ? array_filter([
                'a' => $pick($v['a'] ?? [], $opts[0][1]),
                'b' => $pick($v['b'] ?? [], $opts[1][1]),
            ]) : [],
            'swatch' => array_values(array_unique(array_filter(array_map(fn ($x) => $int($x, 1, 10), $list($v))))),
            'range' => is_array($v) ? array_filter([
                'min' => $int($v['min'] ?? null, $opts['min'], $opts['max']),
                'max' => $int($v['max'] ?? null, $opts['min'], $opts['max']),
            ], fn ($x) => $x !== null) : [],
            'levels', 'reach' => is_array($v) ? collect($v)
                ->filter(fn ($level, $name) => in_array($name, $type === 'levels' ? $opts[0] : self::PLATFORMS, true)
                    && in_array($level, $type === 'levels' ? $opts[1] : $opts, true))
                ->all() : [],
            'pairs' => array_values(array_filter(array_map(
                fn ($p) => is_array($p) && in_array($p[0] ?? null, $opts, true) && in_array($p[1] ?? null, $opts, true) && $p[0] !== $p[1] ? [$p[0], $p[1]] : null,
                array_slice($list($v), 0, 20),
            ))),
            'licence' => is_array($v) ? array_filter([
                'cert' => $str($v['cert'] ?? '', 64),
                'reg' => $str($v['reg'] ?? '', 64),
                'exp' => is_string($v['exp'] ?? null) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v['exp']) ? $v['exp'] : '',
            ], fn ($x) => $x !== '') : [],
            default => null,
        };
    }

    /**
     * Whether a value counts as filled in.
     */
    public static function filled(string $id, mixed $v): bool
    {
        if ($v === null || $v === '' || $v === []) {
            return false;
        }
        if (is_array($v)) {
            return collect($v)->contains(fn ($x) => $x !== '' && $x !== null && $x !== []);
        }

        return true;
    }

    /**
     * The public "About the practice" facts for one skill: [[label, html]].
     * Comfort and coverage is for brands, never visitors (`$showComfort`);
     * digitals are their own block, never a fact; software is left out when
     * the tab already shows the Software block.
     *
     * @param  array<string, mixed>  $values  stored + derived values
     * @return list<array{0: string, 1: string}>
     */
    public static function facts(string $slug, array $values, bool $showComfort, bool $hasSoftwareBlock = false): array
    {
        $defs = self::definitions();
        $facts = [];

        foreach (self::forSkill($slug) as $field) {
            $id = $field['id'];
            if ($id === 'digitals' || ($id === 'comfort' && ! $showComfort) || ($id === 'software' && $hasSoftwareBlock)) {
                continue;
            }
            $html = self::view($id, $values[$id] ?? null, $values);
            if ($html !== '') {
                $facts[] = [$defs[$id][0], $html];
            }
        }

        return $facts;
    }

    /**
     * One value as escaped HTML (port of fields.js view()).
     *
     * @param  array<string, mixed>  $all
     */
    public static function view(string $id, mixed $v, array $all = []): string
    {
        if (! self::filled($id, $v)) {
            return '';
        }
        [, $type, $o] = self::definitions()[$id];
        $e = fn ($s) => e((string) $s);
        $pills = fn (array $l) => '<span class="fx-pills">'.implode('', array_map(fn ($x) => '<span class="chip">'.$e($x).'</span>', $l)).'</span>';
        $inch = function (int $cm): string {
            $i = (int) round($cm / 2.54);

            return intdiv($i, 12).'′'.($i % 12).'″';
        };
        $date = fn (string $d) => CarbonImmutable::parse($d)->locale(app()->getLocale())->isoFormat('D MMM YYYY');

        return match ($type) {
            'num' => $e($v).' '.$e($o['unit']).' <span class="fx-dim">'.$inch((int) $v).'</span>',
            'nums' => implode(' · ', array_map(fn ($p) => $e($p[1]).' '.$e($v[$p[0]]), array_values(array_filter($o, fn ($p) => ! empty($v[$p[0]]))))).' cm',
            'sizes' => implode(' · ', array_filter([
                ! empty($v['c']) ? $e(($v['r'] ?? 'EU').' '.$v['c']) : '',
                ! empty($v['s']) ? 'Shoe '.$e($v['s']) : '',
            ])),
            'one' => $e($v),
            'many' => $pills($v),
            'two' => implode('', array_map(
                fn ($g) => '<span class="fx-dim fx-gl">'.$e($g[1]).'</span>'.$pills($v[$g[0]]),
                array_values(array_filter([['a', $o[0][0]], ['b', $o[1][0]]], fn ($g) => ! empty($v[$g[0]]))),
            )),
            'swatch' => '<span class="fx-sw view">'.implode('', array_map(fn ($i) => '<span style="background:'.$o[$i - 1].'" title="Monk '.$i.'"></span>', collect($v)->sort()->values()->all()))
                .'</span><span class="fx-vh">Monk '.$e(implode(', ', $v)).'</span>',
            'range' => $e(($v['min'] ?? '?').'–'.($v['max'] ?? '?')).' years',
            'levels' => implode(' · ', array_map(fn ($d) => $e($d).' <span class="fx-dim">'.$e($v[$d]).'</span>', array_keys($v))),
            'reach' => (function () use ($v, $all, $e) {
                $platforms = (array) ($all['platforms'] ?? []);
                $keys = array_values(array_filter(array_keys($v), fn ($p) => in_array($p, $platforms, true)));

                return implode(' · ', array_map(fn ($p) => $e($p).' <span class="fx-dim">'.$e($v[$p]).'</span>', $keys));
            })(),
            'pairs' => implode(' · ', array_map(fn ($p) => $e($p[0]).' → '.$e($p[1]), $v)),
            'licence' => 'Licensed remote pilot'.(! empty($v['exp']) ? ' <span class="fx-dim">valid until '.$e($date($v['exp'])).'</span>' : ''),
            default => '',
        };
    }
}
