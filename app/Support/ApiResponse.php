<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * Builds Fama's single JSON envelope, used identically by web-Ajax controllers
 * and the mobile API:
 *
 *   { success, data, message, errors, meta }
 *
 * Prefer the response() macros registered in AppServiceProvider
 * (response()->success(...) / ->error(...) / ->paginated(...)); this class is
 * their implementation and can also be called directly.
 */
final class ApiResponse
{
    /**
     * A successful envelope. `$meta` is merged into the top-level `meta` key.
     *
     * @param  array<string, mixed>  $meta
     */
    public static function success(
        mixed $data = null,
        ?string $message = null,
        array $meta = [],
        int $status = 200,
    ): JsonResponse {
        return self::make(true, $data, $message, null, $meta ?: null, $status);
    }

    /**
     * An error envelope. `$errors` is the field => messages bag (validation
     * shape); `$data` is optional supplementary payload.
     *
     * @param  array<string, mixed>|null  $errors
     * @param  array<string, mixed>  $meta
     */
    public static function error(
        ?string $message = null,
        ?array $errors = null,
        int $status = 400,
        mixed $data = null,
        array $meta = [],
    ): JsonResponse {
        return self::make(false, $data, $message, $errors, $meta ?: null, $status);
    }

    /**
     * A successful envelope for a paginated list. The items become `data` and
     * pagination metadata is placed at `meta.pagination`.
     *
     * @param  array<string, mixed>  $meta
     */
    public static function paginated(
        LengthAwarePaginator $paginator,
        mixed $data = null,
        ?string $message = null,
        array $meta = [],
        int $status = 200,
    ): JsonResponse {
        $meta['pagination'] = [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];

        return self::make(true, $data ?? $paginator->items(), $message, null, $meta, $status);
    }

    /**
     * Assemble and return the envelope as a JsonResponse.
     *
     * @param  array<string, mixed>|null  $errors
     * @param  array<string, mixed>|null  $meta
     */
    private static function make(
        bool $success,
        mixed $data,
        ?string $message,
        ?array $errors,
        ?array $meta,
        int $status,
    ): JsonResponse {
        return new JsonResponse([
            'success' => $success,
            'data' => $data,
            'message' => $message,
            'errors' => $errors,
            'meta' => $meta,
        ], $status);
    }
}
