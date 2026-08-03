<?php

namespace App\Console\Commands;

use App\Services\CounterService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Nightly safety net for the denormalized feed counters (ADR-F6): recomputes
 * every counter from its source table, repairs drift in place, and reports
 * what it fixed. Zero drift is the expected output — anything else points at
 * a counter-maintenance bug worth reading the feed log for.
 */
class ReconcileFeedCounters extends Command
{
    protected $signature = 'feed:reconcile-counters';

    protected $description = 'Recompute denormalized feed counters from source and repair drift';

    /**
     * Run the reconciliation and report per-column repair counts.
     */
    public function handle(CounterService $counters): int
    {
        $repaired = $counters->reconcile();
        $total = array_sum($repaired);

        foreach ($repaired as $column => $count) {
            $this->line(sprintf('%s: %d repaired', $column, $count));
        }

        if ($total > 0) {
            Log::channel('feed')->warning('Feed counter drift repaired', $repaired);
            $this->warn(sprintf('%d counter(s) had drifted and were repaired.', $total));
        } else {
            $this->info('All feed counters were consistent.');
        }

        return self::SUCCESS;
    }
}
