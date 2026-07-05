<?php

use App\Models\User;
use App\Services\Service;
use Illuminate\Support\Facades\Log;

/**
 * Concrete probe service exposing the base transaction+logging helper so we can
 * verify the failure convention (rollback + log to a dedicated channel + rethrow).
 */
class TxProbeService extends Service
{
    public function run(Closure $work): mixed
    {
        return $this->runInTransaction($work, ['probe' => true], 'app');
    }
}

it('commits and returns the value on success', function () {
    $result = (new TxProbeService())->run(fn () => 42);

    expect($result)->toBe(42);
});

it('rolls back writes, logs to the channel, and rethrows on failure', function () {
    // Expect the failure to be routed to the dedicated 'app' channel. andReturnSelf()
    // keeps the Log::channel(...)->error(...) chain working under the mock.
    Log::shouldReceive('channel')->with('app')->once()->andReturnSelf();
    Log::shouldReceive('error')->once();

    $service = new TxProbeService();

    expect(fn () => $service->run(function () {
        User::create([
            'name' => 'Rollback',
            'email' => 'rollback@example.com',
            'password' => 'secret-password',
        ]);

        throw new RuntimeException('boom');
    }))->toThrow(RuntimeException::class, 'boom');

    // Transaction rolled back: the row must not exist.
    expect(User::where('email', 'rollback@example.com')->exists())->toBeFalse();
});
