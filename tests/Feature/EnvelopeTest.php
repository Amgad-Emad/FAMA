<?php

use Illuminate\Pagination\LengthAwarePaginator;

// The response() macros are registered in AppServiceProvider and are how every
// controller returns the shared JSON envelope.

it('exposes a success macro that returns the envelope', function () {
    $response = response()->success(['id' => 1], 'done');

    expect($response->status())->toBe(200);
    expect($response->getData(true))->toMatchArray([
        'success' => true,
        'data' => ['id' => 1],
        'message' => 'done',
        'errors' => null,
    ]);
});

it('exposes an error macro that returns the envelope', function () {
    $response = response()->error('Nope', ['field' => ['bad']], 422);

    expect($response->status())->toBe(422);
    expect($response->getData(true)['success'])->toBeFalse();
    expect($response->getData(true)['errors'])->toBe(['field' => ['bad']]);
});

it('exposes a paginated macro that fills meta.pagination', function () {
    $paginator = new LengthAwarePaginator([1, 2, 3], total: 3, perPage: 15, currentPage: 1);

    $body = response()->paginated($paginator)->getData(true);

    expect($body['success'])->toBeTrue();
    expect($body['meta']['pagination']['total'])->toBe(3);
    expect($body['meta']['pagination']['per_page'])->toBe(15);
});

it('renders validation errors as the envelope for json requests', function () {
    // Posting to login without credentials, asking for JSON, should yield the
    // error envelope (bootstrap/app.php) rather than a redirect.
    $response = $this->postJson('/login', []);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'data' => null,
    ]);
    expect($response->json('errors'))->toHaveKeys(['email', 'password']);
});
