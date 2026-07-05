<?php

namespace App\Events;

use App\Models\Talent;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when a public talent profile is viewed. Decoupled so counting (and later
 * brand_signals enrichment) happens off the request's critical path.
 */
class TalentProfileViewed
{
    use Dispatchable;

    public function __construct(public readonly Talent $talent) {}
}
