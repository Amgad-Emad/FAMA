<?php

namespace App\Http\Controllers\Talent;

use App\Http\Controllers\Controller;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Model;

/**
 * Base controller for the talent dashboard. Everything is scoped to the
 * authenticated talent (the `talent` guard); ensureOwns() enforces the standing
 * rule "a talent may only manage its own resources" (the same rule the
 * App\Policies encode) with a 403 on any foreign resource.
 */
abstract class TalentController extends Controller
{
    protected function talent(): Talent
    {
        return auth('talent')->user();
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
