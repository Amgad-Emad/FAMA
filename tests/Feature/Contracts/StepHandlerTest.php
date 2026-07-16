<?php

use App\Contracting\StepHandlerFactory;
use App\Models\Contract;
use App\Models\ContractStep;
use Illuminate\Validation\ValidationException;

function handler(string $type)
{
    return app(StepHandlerFactory::class)->for($type);
}

it('form handler validates fields and writes the agreed amount', function () {
    $contract = Contract::factory()->create();
    $step = ContractStep::factory()->for($contract)->create(['step_type' => 'form', 'settings' => ['amount_field' => 'amount', 'fields' => ['amount']]]);

    $payload = handler('form')->validate($step, ['fields' => ['amount' => 2500]]);
    handler('form')->apply($contract, $step, $payload);

    expect((float) $contract->fresh()->agreed_amount)->toBe(2500.0);
});

it('form handler rejects a missing required amount', function () {
    $step = ContractStep::factory()->create(['step_type' => 'form', 'settings' => ['amount_field' => 'amount']]);

    expect(fn () => handler('form')->validate($step, ['fields' => ['note' => 'x']]))
        ->toThrow(ValidationException::class);
});

it('payment handler is manual by default and automatic when configured (ADR-B)', function () {
    $manual = ContractStep::factory()->create(['step_type' => 'payment', 'actor' => 'brand', 'settings' => ['confirmation' => 'manual']]);
    $auto = ContractStep::factory()->create(['step_type' => 'payment', 'actor' => 'brand', 'settings' => ['confirmation' => 'auto']]);
    $default = ContractStep::factory()->create(['step_type' => 'payment', 'actor' => 'brand', 'settings' => []]);

    expect(handler('payment')->isAutomatic($manual))->toBeFalse();
    expect(handler('payment')->isAutomatic($auto))->toBeTrue();
    expect(handler('payment')->isAutomatic($default))->toBeFalse();
});

it('treats system-actor steps as automatic', function () {
    $step = ContractStep::factory()->create(['step_type' => 'info', 'actor' => 'system']);

    expect(handler('info')->isAutomatic($step))->toBeTrue();
});

it('upload handler requires at least one attachment', function () {
    $step = ContractStep::factory()->create(['step_type' => 'upload']);

    expect(fn () => handler('upload')->validate($step, ['attachments' => []]))->toThrow(ValidationException::class);
    expect(handler('upload')->validate($step, ['attachments' => ['a.jpg']]))->toBe(['attachments' => ['a.jpg']]);
});

it('schedule handler writes dates onto the contract', function () {
    $contract = Contract::factory()->create();
    $step = ContractStep::factory()->for($contract)->create(['step_type' => 'schedule']);

    $payload = handler('schedule')->validate($step, ['start_date' => '2026-08-01', 'end_date' => '2026-08-03']);
    handler('schedule')->apply($contract, $step, $payload);

    expect($contract->fresh()->start_date->toDateString())->toBe('2026-08-01');
});

it('throws for an unknown step type', function () {
    expect(fn () => app(StepHandlerFactory::class)->for('nope'))->toThrow(InvalidArgumentException::class);
});
