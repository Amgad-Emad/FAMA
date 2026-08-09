<?php

namespace App\Support;

/**
 * Release feature flags (config/features.php). A thin, testable accessor over the
 * `features.*` config so a release can hide whole product areas (brand, contracts,
 * messaging) while the code stays in place. Read at request time so per-request
 * config overrides (tests) take effect.
 */
class Features
{
    public static function enabled(string $name): bool
    {
        return (bool) config("features.{$name}", false);
    }

    public static function brands(): bool
    {
        return self::enabled('brands');
    }

    public static function contracts(): bool
    {
        return self::enabled('contracts');
    }

    public static function messaging(): bool
    {
        return self::enabled('messaging');
    }
}
