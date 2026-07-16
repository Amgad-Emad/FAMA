<?php

namespace App\Actions\Contracts;

/**
 * Contract marker for single-purpose Action classes.
 *
 * Convention (see CLAUDE.md pattern map):
 *  - One action does exactly one discrete operation (seed profile blocks,
 *    snapshot flow steps, initiate/advance a contract, convert enquiry -> contract,
 *    recalc credibility, ...).
 *  - Actions are invokable: implement `public function __invoke(...)` with your
 *    own typed parameters and return type.
 *  - Actions are orchestrated by services and may be dispatched from queues.
 *  - Multi-write actions run inside a transaction (via the calling Service or
 *    DB::transaction) and follow the failure-logging convention.
 *
 * Implementations `use App\Actions\Concerns\...` as needed and declare this
 * interface to opt into the convention and to be discoverable.
 */
interface Action
{
}
