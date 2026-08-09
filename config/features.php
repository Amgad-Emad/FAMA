<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Release feature flags (V1 scoping)
    |--------------------------------------------------------------------------
    |
    | Gate whole product areas so a release can ship a subset of the app while
    | the rest stays in the codebase, dormant, ready to re-enable in a later
    | version. Flags DEFAULT ON so dev/staging + the test suite run the full
    | app; the PRODUCTION .env turns them off for the V1 talent-only release:
    |
    |   FEATURE_BRANDS=false
    |   FEATURE_CONTRACTS=false
    |   FEATURE_MESSAGING=false
    |
    | Coupling: contracts and messaging imply brands (a contract is brand↔talent;
    | the profile "Message" CTA opens a contract). Read via App\Support\Features
    | or the @feature('brands') Blade directive; enforced at request time by the
    | `feature:<name>` middleware.
    |
    */

    'brands' => env('FEATURE_BRANDS', true),

    'contracts' => env('FEATURE_CONTRACTS', true),

    'messaging' => env('FEATURE_MESSAGING', true),

    // Dark mode: when off, the theme toggle is hidden and the app is forced to
    // light (V1 ships light-only). FEATURE_DARK_MODE=false in the production .env.
    'dark_mode' => env('FEATURE_DARK_MODE', true),

];
