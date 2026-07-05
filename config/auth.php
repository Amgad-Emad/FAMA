<?php

use App\Models\Brand;
use App\Models\Talent;
use App\Models\User;

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | Fama has THREE login entities, each with its own guard + provider:
    |   admin  -> users   (App\Models\User)     the platform staff table
    |   brand  -> brands  (App\Models\Brand)    brand accounts
    |   talent -> talents (App\Models\Talent)   talent accounts
    |
    | The default guard is `admin` because `users` is the only auth table
    | migrated in Phase 0 (brands/talents tables land in Phase 1). Keeping the
    | default aligned with the migrated table also keeps the Breeze User-based
    | scaffolding tests green. Session logins are used for the web app; Sanctum
    | tokens (config/sanctum.php) are reserved for the mobile API (Phase 4).
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'admin'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | One session guard per login entity. The mobile API authenticates with the
    | `sanctum` token guard, which falls back to these session guards for SPA
    | (stateful) requests — see config/sanctum.php.
    |
    */

    'guards' => [
        'admin' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'brand' => [
            'driver' => 'session',
            'provider' => 'brands',
        ],

        'talent' => [
            'driver' => 'session',
            'provider' => 'talents',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | Each guard resolves its Authenticatable through an Eloquent provider.
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', User::class),
        ],

        'brands' => [
            'driver' => 'eloquent',
            'model' => Brand::class,
        ],

        'talents' => [
            'driver' => 'eloquent',
            'model' => Talent::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | One broker per entity. Brands/talents share the default token table for
    | now; dedicated reset flows are wired when their tables land in Phase 1.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],

        'brands' => [
            'provider' => 'brands',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        'talents' => [
            'provider' => 'talents',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
