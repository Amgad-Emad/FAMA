<?php

namespace App\Http\Controllers\Api\V1\Talent;

use App\Http\Controllers\Controller;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Base controller for the talent mobile API. Everything is scoped to the talent
 * behind the presented Sanctum token (the `talent`-ability tokenable resolved by
 * `auth:sanctum`); `ensureOwns()` mirrors the web dashboard's rule — a talent may
 * only manage its own resources — with a 403 on any foreign row. Controllers stay
 * thin and delegate to the existing talent services.
 */
abstract class TalentApiController extends Controller
{
    /**
     * The authenticated talent (the token's tokenable).
     */
    protected function talent(): Talent
    {
        /** @var Talent */
        return Auth::guard('sanctum')->user();
    }

    /**
     * Abort with 403 unless the model belongs to the authenticated talent.
     */
    protected function ensureOwns(Model $model): void
    {
        abort_unless(
            (int) $model->getAttribute('talent_id') === (int) $this->talent()->getKey(),
            403,
        );
    }
}
