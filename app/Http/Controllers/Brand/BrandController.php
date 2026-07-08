<?php

namespace App\Http\Controllers\Brand;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Database\Eloquent\Model;

/**
 * Base controller for the brand dashboard (the `brand` guard). Everything is
 * scoped to the authenticated brand; ensureOwns() enforces "a brand may only
 * manage its own resources" with a 403 on any foreign resource.
 */
abstract class BrandController extends Controller
{
    protected function brand(): Brand
    {
        return auth('brand')->user();
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
