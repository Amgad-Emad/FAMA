<?php

namespace App\Http\Controllers\Talent;

use App\Http\Controllers\Controller;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

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
     * Authorize a policy ability for the signed-in TALENT (the default guard is
     * not the talent guard, so Gate is pointed at the talent explicitly).
     * Used for projects, whose owners include accepted co-owners (ProjectPolicy),
     * where the creator-only ensureOwns() would be wrong. 403 on denial.
     */
    protected function authorizeTalent(string $ability, Model $model): void
    {
        Gate::forUser($this->talent())->authorize($ability, $model);
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
