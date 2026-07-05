<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * Base DTO for Fama, built on spatie/laravel-data.
 *
 * DTOs are the typed contract that flows Form Request -> Service -> Resource,
 * shared between the web and API layers so both speak the same shapes. Concrete
 * DTOs extend this class, declare typed public properties, and (where useful)
 * add validation attributes and `from*()` factories.
 */
abstract class BaseData extends Data
{
}
