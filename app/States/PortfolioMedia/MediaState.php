<?php

namespace App\States\PortfolioMedia;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Media/portfolio lifecycle (talent-spec):
 * uploaded → processed (thumbnail generated) → ordered → visible → archived.
 * `Processed` is reached via App\Listeners\ProcessPortfolioMedia when the
 * medialibrary conversion completes.
 */
abstract class MediaState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Uploaded::class)
            ->allowTransition(Uploaded::class, Processed::class)
            ->allowTransition(Processed::class, Ordered::class)
            ->allowTransition(Ordered::class, Visible::class)
            ->allowTransition(Visible::class, Archived::class);
    }
}
