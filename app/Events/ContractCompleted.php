<?php

namespace App\Events;

use App\Models\Contract;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when a contract reaches the terminal `completed` state (ContractProgression).
 * Decouples completion side effects — brand credibility accrual, opening the
 * talent's brand-review window — from the contract engine.
 */
class ContractCompleted
{
    use Dispatchable;

    public function __construct(public readonly Contract $contract) {}
}
