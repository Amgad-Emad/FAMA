<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * The location vocabulary behind the City and Country drop lists: 250
 * countries (ISO code + English name, which is what `base_country` stores)
 * and, per country, its cities of 15,000+ people, largest first.
 *
 * Data lives in resources/data/locations (built from GeoNames, CC BY 4.0);
 * flags are public/fama-ui/img/flags/{code}.svg (flag-icons, MIT).
 */
class Locations
{
    /** Short or older names people typed before the drop list existed. */
    private const ALIASES = [
        'uae' => 'AE', 'emirates' => 'AE', 'ksa' => 'SA', 'saudi' => 'SA',
        'usa' => 'US', 'us' => 'US', 'america' => 'US', 'uk' => 'GB', 'britain' => 'GB',
        'great britain' => 'GB', 'england' => 'GB', 'palestinian territories' => 'PS',
    ];

    /**
     * Every country, sorted by name.
     *
     * @return list<array{code: string, name: string}>
     */
    public static function countries(): array
    {
        return Cache::rememberForever('locations:countries:v1', fn (): array => json_decode(
            (string) file_get_contents(resource_path('data/locations/countries.json')),
            true,
        ));
    }

    /**
     * Every country name (the values `base_country` accepts).
     *
     * @return list<string>
     */
    public static function countryNames(): array
    {
        return array_column(self::countries(), 'name');
    }

    /** The ISO code for a stored country name or a known alias (null when unknown). */
    public static function codeFor(?string $country): ?string
    {
        $key = mb_strtolower(trim((string) $country));
        if ($key === '') {
            return null;
        }
        foreach (self::countries() as $row) {
            if (mb_strtolower($row['name']) === $key || mb_strtolower($row['code']) === $key) {
                return $row['code'];
            }
        }

        return self::ALIASES[$key] ?? null;
    }

    /** The canonical country name for a stored value (the value itself when unknown). */
    public static function canonicalName(?string $country): ?string
    {
        $code = self::codeFor($country);
        if ($code === null) {
            return $country;
        }
        foreach (self::countries() as $row) {
            if ($row['code'] === $code) {
                return $row['name'];
            }
        }

        return $country;
    }

    /**
     * The cities of one country, largest first ([] for an unknown code).
     *
     * @return list<string>
     */
    public static function cities(string $code): array
    {
        $code = strtoupper($code);
        if (! preg_match('/^[A-Z]{2}$/', $code)) {
            return [];
        }
        $path = resource_path("data/locations/cities/{$code}.json");

        return is_file($path) ? json_decode((string) file_get_contents($path), true) : [];
    }
}
