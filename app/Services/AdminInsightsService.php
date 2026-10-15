<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Read-only platform insights for the admin dashboard: time series over a
 * chosen range (sign-ups, verifications, posts, profile views), period totals
 * with the change against the previous equal period, the verification funnel
 * and the skills mix.
 *
 * Every series is ONE grouped query, zero-filled in PHP so the chart's time
 * axis is continuous. Buckets: day for 7d/30d, ISO week for 90d, month for 12m.
 */
class AdminInsightsService extends Service
{
    /** The ranges the dashboard offers. */
    public const RANGES = ['7d', '30d', '90d', '12m'];

    /**
     * Series + totals for one range.
     *
     * @return array<string, mixed>
     */
    public function insights(string $range): array
    {
        [$from, $to, $bucket] = $this->window($range);
        // The previous equal-length period, for the change figures.
        $days = (int) $from->diffInDays($to) + 1;
        [$prevFrom, $prevTo] = [$from->subDays($days), $from->subDay()];

        $keys = $this->bucketKeys($from, $to, $bucket);

        $metrics = [
            'signups' => fn ($a, $b, $g) => $this->countBy('talents', 'created_at', $a, $b, $g, fn ($q) => $q->whereNull('deleted_at')),
            'verified' => fn ($a, $b, $g) => $this->countBy('activity_log', 'created_at', $a, $b, $g, fn ($q) => $q->where('description', 'talent.verified')),
            'posts' => fn ($a, $b, $g) => $this->countBy('posts', 'published_at', $a, $b, $g, fn ($q) => $q->whereNull('deleted_at')->where('state', 'published')),
            'views' => fn ($a, $b, $g) => $this->sumBy('talent_profile_views', 'viewed_on', 'views', $a, $b, $g),
        ];

        $series = [];
        $totals = [];
        foreach ($metrics as $name => $query) {
            $rows = $query($from, $to, $bucket);
            $series[$name] = array_map(fn (string $k) => (int) ($rows[$k] ?? 0), $keys);
            $now = array_sum($series[$name]);
            $before = array_sum($query($prevFrom, $prevTo, $bucket));
            $totals[$name] = ['value' => $now, 'previous' => $before, 'delta_pct' => $before > 0 ? (int) round(($now - $before) / $before * 100) : null];
        }

        // Per-KPI detail figures (the three boxes under the chart change with the picked tile).
        $pair = fn (callable $q) => ['value' => (int) $q($from, $to), 'previous' => (int) $q($prevFrom, $prevTo)];
        $now = fn (callable $q) => ['value' => (int) $q(), 'previous' => null];
        $newTalents = fn ($a, $b) => DB::table('talents')->whereNull('deleted_at')->whereBetween('created_at', [$a->startOfDay(), $b->endOfDay()]);
        $count = fn (string $table, ?callable $scope = null) => function ($a, $b) use ($table, $scope) {
            $q = DB::table($table)->whereBetween('created_at', [$a->startOfDay(), $b->endOfDay()]);
            if ($scope) {
                $scope($q);
            }

            return $q->count();
        };
        $viewedTalents = fn ($a, $b) => DB::table('talent_profile_views')->whereBetween('viewed_on', [$a->toDateString(), $b->toDateString()])->distinct()->count('talent_id');

        $details = [
            'signups' => [
                'completed' => $pair(fn ($a, $b) => $newTalents($a, $b)->where(fn ($w) => $w->whereNotNull('steps_completed_at')->orWhere('is_verified', true))->count()),
                'live' => $pair(fn ($a, $b) => $newTalents($a, $b)->where('is_published', true)->count()),
                'per_day' => ['value' => round($totals['signups']['value'] / $days, 1), 'previous' => round($totals['signups']['previous'] / $days, 1)],
            ],
            'verified' => [
                'verified_live' => $pair(fn ($a, $b) => DB::table('activity_log')->join('talents', 'talents.id', '=', 'activity_log.subject_id')
                    ->where('activity_log.subject_type', 'talent')->where('activity_log.description', 'talent.verified')->where('talents.is_published', true)
                    ->whereBetween('activity_log.created_at', [$a->startOfDay(), $b->endOfDay()])->count()),
                'awaiting' => $now(fn () => DB::table('talents')->whereNull('deleted_at')->where('is_verified', false)->where('status', '!=', 'suspended')->count()),
                'ready' => $now(fn () => DB::table('talents')->whereNull('deleted_at')->where('is_verified', false)->whereNotNull('steps_completed_at')->count()),
            ],
            'posts' => [
                'likes' => $pair($count('likes')),
                'comments' => $pair($count('comments', fn ($q) => $q->whereNull('deleted_at'))),
                'posters' => $pair(fn ($a, $b) => DB::table('posts')->whereNull('deleted_at')->where('state', 'published')
                    ->whereBetween('published_at', [$a->startOfDay(), $b->endOfDay()])->distinct()->count(DB::raw("CONCAT(author_type, ':', author_id)"))),
            ],
            'views' => [
                'viewed' => $pair($viewedTalents),
                'per_talent' => (function () use ($totals, $viewedTalents, $from, $to, $prevFrom, $prevTo) {
                    $n = $viewedTalents($from, $to);
                    $p = $viewedTalents($prevFrom, $prevTo);

                    return ['value' => $n ? round($totals['views']['value'] / $n, 1) : 0, 'previous' => $p ? round($totals['views']['previous'] / $p, 1) : 0];
                })(),
                'follows' => $pair($count('follows')),
            ],
        ];
        foreach ($details as &$set) {
            foreach ($set as &$d) {
                $d['delta_pct'] = $d['previous'] ? (int) round(($d['value'] - $d['previous']) / $d['previous'] * 100) : null;
            }
        }
        unset($set, $d);

        return [
            'range' => $range,
            'bucket' => $bucket,
            'labels' => array_map(fn (string $k) => $this->label($k, $bucket), $keys),
            'series' => $series,
            'totals' => $totals,
            'details' => $details,
        ];
    }

    /**
     * Where talents are on the way to Live, right now: signed up → six steps
     * done → checked by Fama → Live.
     *
     * @return list<array{key: string, value: int}>
     */
    public function funnel(): array
    {
        $row = DB::table('talents')->whereNull('deleted_at')->selectRaw(
            'COUNT(*) AS signed_up,
             SUM(CASE WHEN steps_completed_at IS NOT NULL OR is_verified = 1 THEN 1 ELSE 0 END) AS completed,
             SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END) AS verified,
             SUM(CASE WHEN is_published = 1 THEN 1 ELSE 0 END) AS live'
        )->first();

        return collect(['signed_up', 'completed', 'verified', 'live'])
            ->map(fn (string $k) => ['key' => $k, 'value' => (int) ($row->{$k} ?? 0)])->all();
    }

    /**
     * How many talents hold each skill, largest first.
     *
     * @return list<array{name: string, value: int}>
     */
    public function skillsMix(): array
    {
        $locale = app()->getLocale();

        return DB::table('talent_types')
            ->leftJoin('talent_talent_type', 'talent_talent_type.talent_type_id', '=', 'talent_types.id')
            ->groupBy('talent_types.id', 'talent_types.name')
            ->orderByDesc('n')
            ->get(['talent_types.name', DB::raw('COUNT(talent_talent_type.talent_id) AS n')])
            ->map(function ($r) use ($locale) {
                $name = json_decode((string) $r->name, true);

                return ['name' => is_array($name) ? ($name[$locale] ?? $name['en'] ?? '') : (string) $r->name, 'value' => (int) $r->n];
            })->values()->all();
    }

    /**
     * [from, to, bucket] for a range (inclusive dates).
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: string}
     */
    private function window(string $range): array
    {
        $to = CarbonImmutable::today();

        return match ($range) {
            '7d' => [$to->subDays(6), $to, 'day'],
            '90d' => [$to->subDays(89)->startOfWeek(), $to, 'week'],
            '12m' => [$to->subMonths(11)->startOfMonth(), $to, 'month'],
            default => [$to->subDays(29), $to, 'day'],
        };
    }

    /** The SQL expression that names a row's bucket. */
    private function bucketSql(string $column, string $bucket): string
    {
        return match ($bucket) {
            'week' => "DATE_FORMAT(DATE_SUB(DATE({$column}), INTERVAL WEEKDAY({$column}) DAY), '%Y-%m-%d')",
            'month' => "DATE_FORMAT({$column}, '%Y-%m')",
            default => "DATE_FORMAT({$column}, '%Y-%m-%d')",
        };
    }

    /**
     * Every bucket key in the window, in order (zero-fill).
     *
     * @return list<string>
     */
    private function bucketKeys(CarbonImmutable $from, CarbonImmutable $to, string $bucket): array
    {
        $keys = [];
        $cursor = match ($bucket) {
            'week' => $from->startOfWeek(),
            'month' => $from->startOfMonth(),
            default => $from,
        };
        while ($cursor->lte($to)) {
            $keys[] = $bucket === 'month' ? $cursor->format('Y-m') : $cursor->format('Y-m-d');
            $cursor = match ($bucket) {
                'week' => $cursor->addWeek(),
                'month' => $cursor->addMonth(),
                default => $cursor->addDay(),
            };
        }

        return $keys;
    }

    /** A short, localized axis label for a bucket key. */
    private function label(string $key, string $bucket): string
    {
        $date = $bucket === 'month' ? CarbonImmutable::createFromFormat('Y-m', $key)->startOfMonth() : CarbonImmutable::parse($key);

        return $bucket === 'month' ? $date->translatedFormat('M Y') : $date->translatedFormat('j M');
    }

    /**
     * Row counts per bucket.
     *
     * @return array<string, int>
     */
    private function countBy(string $table, string $column, CarbonImmutable $from, CarbonImmutable $to, string $bucket, ?callable $scope = null): array
    {
        $query = DB::table($table)
            ->whereBetween($column, [$from->startOfDay(), $to->endOfDay()])
            ->selectRaw($this->bucketSql($column, $bucket).' AS k, COUNT(*) AS n')
            ->groupBy('k');
        if ($scope) {
            $scope($query);
        }

        return $query->pluck('n', 'k')->map(fn ($n) => (int) $n)->all();
    }

    /**
     * Sums of one column per bucket.
     *
     * @return array<string, int>
     */
    private function sumBy(string $table, string $column, string $sum, CarbonImmutable $from, CarbonImmutable $to, string $bucket): array
    {
        return DB::table($table)
            ->whereBetween($column, [$from->toDateString(), $to->toDateString()])
            ->selectRaw($this->bucketSql($column, $bucket).' AS k, SUM('.$sum.') AS n')
            ->groupBy('k')
            ->pluck('n', 'k')->map(fn ($n) => (int) $n)->all();
    }
}
