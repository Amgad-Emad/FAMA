<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Base class for the service layer. Services own business logic, wrap
 * multi-write operations in transactions, and are the single place both web and
 * API controllers call into (controllers stay thin — see CLAUDE.md).
 *
 * The failure-logging convention lives here: runInTransaction() catches any
 * throwable, logs it to a dedicated channel WITH context, then rethrows so the
 * caller (or the global exception handler) can turn it into an error envelope.
 */
abstract class Service
{
    /**
     * The default log channel for this service's failures. Override per domain
     * service (e.g. 'contracts', 'media'); defaults to the app channel.
     */
    protected string $logChannel = 'app';

    /**
     * Run a multi-write closure inside a DB transaction with failure logging.
     * On success the transaction commits and the closure's value is returned;
     * on any throwable it rolls back, logs to the channel with context, and
     * rethrows.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @param  array<string, mixed>  $context
     * @return TReturn
     */
    protected function runInTransaction(Closure $callback, array $context = [], ?string $channel = null, int $attempts = 1): mixed
    {
        try {
            return DB::transaction($callback, $attempts);
        } catch (Throwable $e) {
            Log::channel($channel ?? $this->logChannel)->error($e->getMessage(), $context + [
                'service' => static::class,
                'exception' => $e,
            ]);

            throw $e;
        }
    }
}
