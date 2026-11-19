<?php

namespace App\Support;

use App\Models\BrandCollab;
use Illuminate\Support\Collection;

/**
 * Experience as LinkedIn-style roles (ADR-AK) — a port of the prototype's
 * profile.js FAMA_EXP: option lists, the role payload the editor works on,
 * date ranges with durations, and the grouping both the editor and the public
 * profile draw (newest first; consecutive roles at one company grouped under it).
 */
class Experience
{
    public const EMPLOYMENT = ['Freelance', 'Full-time', 'Part-time', 'Contract', 'Internship'];

    public const LOCATION_TYPES = ['On-site', 'Hybrid', 'Remote'];

    public const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    /**
     * One row as the editor's role shape (the prototype's {t,n,et,loc,lt,cur,sm,sy,em,ey,hl,sk}).
     * A row written before roles existed reads its project as the title.
     *
     * @return array<string, mixed>
     */
    public static function role(BrandCollab $row): array
    {
        return [
            'id' => (int) $row->id,
            't' => (string) ($row->role_title ?: ($row->project_title ?: '')),
            'n' => (string) $row->brand_name,
            'et' => (string) ($row->employment_type ?? ''),
            'loc' => (string) ($row->location ?? ''),
            'lt' => (string) ($row->location_type ?? ''),
            'cur' => (bool) $row->is_current,
            'sm' => (int) ($row->start_month ?? 0),
            'sy' => (int) ($row->start_year ?? $row->year ?? 0),
            'em' => (int) ($row->end_month ?? 0),
            'ey' => (int) ($row->end_year ?? 0),
            'hl' => (string) ($row->highlights ?? ''),
            'sk' => array_values(array_map('intval', (array) ($row->skill_ids ?? []))),
        ];
    }

    /**
     * Months a role lasted (at least one); "now" for a current role.
     *
     * @param  array<string, mixed>  $r
     */
    public static function months(array $r): int
    {
        $now = now();
        $ey = $r['cur'] ? (int) $now->year : ($r['ey'] ?: $r['sy']);
        $em = $r['cur'] ? (int) $now->month : ($r['em'] ?: ($r['ey'] ? 12 : ($r['sm'] ?: 12)));
        $sm = $r['sm'] ?: 1;

        return max(1, ($ey - $r['sy']) * 12 + ($em - $sm) + 1);
    }

    /**
     * "2 yrs 3 mos".
     */
    public static function duration(int $months): string
    {
        $y = intdiv($months, 12);
        $m = $months % 12;

        return trim(($y ? $y.($y === 1 ? ' yr' : ' yrs') : '').' '.($m ? $m.($m === 1 ? ' mo' : ' mos') : ''));
    }

    /**
     * "Mar 2024 – Present · 2 yrs 9 mos".
     *
     * @param  array<string, mixed>  $r
     */
    public static function range(array $r): string
    {
        $when = fn (int $m, int $y) => $y ? ($m ? self::MONTHS[$m - 1].' ' : '').$y : '';
        $end = $r['cur'] ? 'Present' : ($when($r['em'], $r['ey']) ?: $when($r['sm'], $r['sy']));

        return $when($r['sm'], $r['sy']).' – '.$end.($r['sy'] ? ' · '.self::duration(self::months($r)) : '');
    }

    /**
     * Newest first (current roles on top, latest start first), then
     * consecutive roles at the same company grouped.
     *
     * @param  Collection<int, BrandCollab>  $rows
     * @return list<array{n: string, roles: list<array<string, mixed>>}>
     */
    public static function groups(Collection $rows): array
    {
        $key = fn (array $r) => $r['cur'] ? 1e6 + $r['sy'] * 12 + ($r['sm'] ?: 1) : ($r['ey'] ?: $r['sy']) * 12 + ($r['em'] ?: 12);
        $roles = $rows->map(fn (BrandCollab $row) => self::role($row))
            ->filter(fn ($r) => $r['n'] !== '' || $r['t'] !== '')
            ->sortByDesc($key)->values();

        $out = [];
        foreach ($roles as $r) {
            $last = array_key_last($out);
            if ($last !== null && mb_strtolower($out[$last]['n']) === mb_strtolower($r['n'])) {
                $out[$last]['roles'][] = $r;
            } else {
                $out[] = ['n' => $r['n'], 'roles' => [$r]];
            }
        }

        return $out;
    }

    /**
     * Total months across a grouped company (first start to last end).
     *
     * @param  list<array<string, mixed>>  $roles  newest first
     */
    public static function groupMonths(array $roles): int
    {
        $first = end($roles);
        $last = $roles[0];

        return self::months(['cur' => $last['cur'], 'sy' => $first['sy'], 'sm' => $first['sm'], 'ey' => $last['ey'] ?: $last['sy'], 'em' => $last['em']]);
    }
}
