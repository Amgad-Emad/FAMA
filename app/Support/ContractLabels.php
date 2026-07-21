<?php

namespace App\Support;

/**
 * Localized display labels for contract-flow enums (actor) and the seeded
 * default step names (keyed by step key). Single source of truth shared by the
 * server-side ContractMessageResource and the client-side design-head label
 * maps — so a contract-timeline event reads the same in Blade and JS.
 */
final class ContractLabels
{
    /**
     * @return array<string, string>
     */
    public static function actorLabels(): array
    {
        return [
            'brand' => __('Brand'), 'talent' => __('Talent'), 'admin' => __('Admin'),
            'system' => __('System'), 'both' => __('Both'),
        ];
    }

    /**
     * Default contract-flow step names, keyed by the stable step key.
     *
     * @return array<string, string>
     */
    public static function stepLabels(): array
    {
        return [
            'brief' => __('Project brief'), 'quote' => __('Talent quote'),
            'agreement' => __('Approve quote'), 'payment' => __('Deposit'),
            'delivery' => __('Deliver work'), 'signoff' => __('Approve delivery'),
            'complete' => __('Contract complete'),
        ];
    }

    public static function actor(?string $value): string
    {
        return self::actorLabels()[$value] ?? ucfirst((string) $value);
    }

    public static function step(?string $key, ?string $fallback = null): string
    {
        return self::stepLabels()[$key] ?? $fallback ?? (string) $key;
    }
}
