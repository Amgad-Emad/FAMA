<?php

namespace App\States\Contract;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Contract lifecycle (talent-spec "contract loop", schema-master §3).
 *
 * draft → awaiting_{brand,talent,admin} (interchangeable as the turn flips) →
 * completed. Terminal exits: cancelled | declined | expired. The `status`
 * column IS the state (no separate projection). "Initiated" is not a persisted
 * status — a contract is considered initiated once it leaves draft into awaiting_*.
 */
abstract class ContractState extends State
{
    public static function config(): StateConfig
    {
        $awaiting = [AwaitingBrand::class, AwaitingTalent::class, AwaitingAdmin::class];
        $terminal = [Cancelled::class, Declined::class, Expired::class];

        $config = parent::config()->default(Draft::class);

        foreach ($awaiting as $to) {
            $config->allowTransition(Draft::class, $to);
        }
        $config->allowTransition(Draft::class, Completed::class); // all-automatic flow edge case

        foreach ($awaiting as $from) {
            foreach ($awaiting as $to) {
                if ($from !== $to) {
                    $config->allowTransition($from, $to);
                }
            }
            $config->allowTransition($from, Completed::class);
        }

        foreach (array_merge([Draft::class], $awaiting) as $from) {
            foreach ($terminal as $to) {
                $config->allowTransition($from, $to);
            }
        }

        return $config;
    }

    /**
     * True while the contract is live and waiting on one of the parties.
     */
    public function isAwaiting(): bool
    {
        return in_array(static::$name, ['awaiting_brand', 'awaiting_talent', 'awaiting_admin'], true);
    }

    /**
     * True once the contract has reached a terminal state (no further transitions).
     */
    public function isTerminal(): bool
    {
        return in_array(static::$name, ['completed', 'cancelled', 'declined', 'expired'], true);
    }
}
