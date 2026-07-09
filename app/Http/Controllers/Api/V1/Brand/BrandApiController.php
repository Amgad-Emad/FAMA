<?php

namespace App\Http\Controllers\Api\V1\Brand;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Base controller for the brand mobile API. Everything is scoped to the brand
 * behind the presented Sanctum token (the `brand`-ability tokenable resolved by
 * `auth:sanctum`); `ensureOwns()` mirrors the web dashboard's rule — a brand may
 * only manage its own resources — with a 403 on any foreign row. Controllers stay
 * thin and delegate to the existing brand services.
 */
abstract class BrandApiController extends Controller
{
    /**
     * The authenticated brand (the token's tokenable).
     */
    protected function brand(): Brand
    {
        /** @var Brand */
        return Auth::guard('sanctum')->user();
    }

    /**
     * Abort with 403 unless the model belongs to the authenticated brand.
     */
    protected function ensureOwns(Model $model): void
    {
        abort_unless(
            (int) $model->getAttribute('brand_id') === (int) $this->brand()->getKey(),
            403,
        );
    }
}
