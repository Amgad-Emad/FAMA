<?php

use App\Support\ApiResponse;
use Illuminate\Pagination\LengthAwarePaginator;

it('builds a success envelope', function () {
    $response = ApiResponse::success(['id' => 1], 'ok', ['k' => 'v']);

    expect($response->status())->toBe(200);

    $body = $response->getData(true);
    expect($body)->toMatchArray([
        'success' => true,
        'data' => ['id' => 1],
        'message' => 'ok',
        'errors' => null,
        'meta' => ['k' => 'v'],
    ]);
});

it('builds an error envelope carrying the field bag', function () {
    $response = ApiResponse::error('Invalid', ['email' => ['The email field is required.']], 422);

    expect($response->status())->toBe(422);

    $body = $response->getData(true);
    expect($body['success'])->toBeFalse();
    expect($body['data'])->toBeNull();
    expect($body['errors'])->toBe(['email' => ['The email field is required.']]);
});

it('places pagination metadata at meta.pagination', function () {
    $paginator = new LengthAwarePaginator([['id' => 1], ['id' => 2]], total: 40, perPage: 15, currentPage: 1);

    $body = ApiResponse::paginated($paginator)->getData(true);

    expect($body['success'])->toBeTrue();
    expect($body['data'])->toHaveCount(2);
    expect($body['meta']['pagination'])->toMatchArray([
        'current_page' => 1,
        'last_page' => 3,
        'per_page' => 15,
        'total' => 40,
        'from' => 1,
        'to' => 2,
    ]);
});
