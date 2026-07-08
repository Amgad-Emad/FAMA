<?php

namespace App\Events;

use App\Models\Deal;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when a deal reaches the terminal `completed` state (DealProgression).
 * Decouples completion side effects — brand credibility accrual, opening the
 * talent's brand-review window — from the deal engine.
 */
class DealCompleted
{
    use Dispatchable;

    public function __construct(public readonly Deal $deal) {}
}
