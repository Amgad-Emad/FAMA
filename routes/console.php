<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly feed housekeeping (ADR-F6 / 5Q): recompute denormalized counters
// and prune stale bookkeeping rows while traffic is lowest.
Schedule::command('feed:reconcile-counters')->dailyAt('03:10');
Schedule::command('feed:prune')->dailyAt('03:40');
