<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Base API/web resource for Fama.
 *
 * Wrapping is disabled ($wrap = null) because Fama's single JSON envelope
 * (App\Support\ApiResponse) provides the outer { success, data, message, ... }
 * structure — a resource represents the `data` payload only, never its own
 * `{ "data": ... }` wrapper. Concrete resources extend this and implement
 * toArray(); collections are returned via `SomeResource::collection($paginator)`
 * and handed to response()->paginated().
 */
abstract class BaseResource extends JsonResource
{
    /**
     * Disable the default "data" wrapping; the envelope owns the outer shape.
     *
     * @var string|null
     */
    public static $wrap = null;
}
