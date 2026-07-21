<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Admin-tunable global settings (schema-master §6). Reads resolve from a cached
 * key→value map; writes upsert and bust the cache, wrapped in a transaction with
 * fail-logging to the `admin` channel. Typed accessors expose the well-known
 * globals (default currency, default contract flow, feature flags).
 */
class SettingsService extends Service
{
    protected string $logChannel = 'admin';

    private const CACHE_KEY = 'fama.settings';

    /**
     * The full key→value map (cached until the next write).
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => Setting::all()->pluck('value', 'key')->all());
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    public function set(string $key, mixed $value): Setting
    {
        return $this->runInTransaction(function () use ($key, $value): Setting {
            $setting = Setting::updateOrCreate(['key' => $key], ['value' => $value]);
            $this->flush();

            return $setting;
        }, ['setting' => $key]);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values): void
    {
        $this->runInTransaction(function () use ($values): void {
            foreach ($values as $key => $value) {
                Setting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
            $this->flush();
        }, ['keys' => array_keys($values)]);
    }

    public function forget(string $key): void
    {
        $this->runInTransaction(function () use ($key): void {
            Setting::where('key', $key)->delete();
            $this->flush();
        }, ['setting' => $key]);
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    // --- Well-known globals -------------------------------------------------

    public function defaultCurrency(): string
    {
        return (string) $this->get('default_currency', 'EGP');
    }

    public function defaultContractFlowId(): ?int
    {
        $value = $this->get('default_contract_flow_id');

        return $value !== null ? (int) $value : null;
    }

    public function featureEnabled(string $flag, bool $default = false): bool
    {
        $flags = $this->get('feature_flags', []);

        return (bool) (is_array($flags) ? ($flags[$flag] ?? $default) : $default);
    }
}
