<?php

namespace App\Services;

use App\Models\Talent;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;

/**
 * Read-model over talent_profile_views for the dashboard views KPI: the
 * week-over-week comparison shown on the stat tile and the bucketed series
 * behind the expandable chart. Read-only — no transactions. Gaps are
 * zero-filled so the chart's time axis is continuous, and bucket labels are
 * localized server-side (Carbon translatedFormat in the app locale).
 */
class ProfileViewStatsService extends Service
{
    /** The range presets the chart filter accepts ('all' = since profile creation). */
    public const RANGES = ['7d', '30d', '90d', 'all'];

    /**
     * Rolling week-over-week comparison for the KPI tile: views in the last
     * 7 days (today inclusive) vs the 7 days before that. delta_pct is null
     * when the previous week has no views (no meaningful percentage).
     *
     * @return array{current_week: int, previous_week: int, delta: int, delta_pct: float|null}
     */
    public function weeklyComparison(Talent $talent): array
    {
        $today = CarbonImmutable::today();

        $counts = $talent->profileViews()
            ->selectRaw("CASE WHEN viewed_on >= ? THEN 'current' ELSE 'previous' END AS bucket, SUM(views) AS total", [$today->subDays(6)->toDateString()])
            ->where('viewed_on', '>=', $today->subDays(13)->toDateString())
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        $current = (int) ($counts['current'] ?? 0);
        $previous = (int) ($counts['previous'] ?? 0);

        return [
            'current_week' => $current,
            'previous_week' => $previous,
            'delta' => $current - $previous,
            'delta_pct' => $previous > 0 ? round((($current - $previous) / $previous) * 100, 1) : null,
        ];
    }

    /**
     * Bucketed view series for the chart. Presets: 7d/30d (daily), 90d
     * (weekly), and 'all' — since the profile was created — with a bucket
     * chosen from the span (daily ≤ 5 weeks, weekly ≤ 26 weeks, else
     * monthly). Buckets with no views are filled with zeros.
     *
     * @return array{range: string, bucket: string, total: int, series: list<array{date: string, label: string, views: int}>}
     */
    public function series(Talent $talent, string $range): array
    {
        $today = CarbonImmutable::today();
        $created = CarbonImmutable::parse($talent->created_at)->startOfDay();

        [$start, $bucket] = match ($range) {
            '7d' => [$today->subDays(6), 'day'],
            '30d' => [$today->subDays(29), 'day'],
            '90d' => [$today->subDays(89)->startOfWeek(), 'week'],
            'all' => [$created, match (true) {
                $created->diffInDays($today) <= 35 => 'day',
                $created->diffInWeeks($today) <= 26 => 'week',
                default => 'month',
            }],
        };

        // Daily sums once; bucket aggregation happens in PHP (talent-scale
        // data — avoids MySQL week/locale functions).
        $daily = $talent->profileViews()
            ->where('viewed_on', '>=', $start->toDateString())
            ->orderBy('viewed_on')
            ->pluck('views', 'viewed_on')
            ->mapWithKeys(fn ($views, $date) => [CarbonImmutable::parse($date)->toDateString() => (int) $views]);

        $series = [];
        foreach (CarbonPeriod::create($start, "1 {$bucket}", $today) as $bucketStart) {
            $bucketStart = CarbonImmutable::instance($bucketStart);
            $bucketEnd = $bucketStart->add($bucket, 1);

            $views = $daily
                ->filter(fn ($v, $date) => $date >= $bucketStart->toDateString() && $date < $bucketEnd->toDateString())
                ->sum();

            $series[] = [
                'date' => $bucketStart->toDateString(),
                'label' => $bucketStart->locale(app()->getLocale())->translatedFormat($bucket === 'month' ? 'M Y' : 'j M'),
                'views' => $views,
            ];
        }

        return [
            'range' => $range,
            'bucket' => $bucket,
            'total' => array_sum(array_column($series, 'views')),
            'series' => $series,
        ];
    }
}
