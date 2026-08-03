<?php

namespace App\Http\Controllers\Feed;

use App\Http\Controllers\Controller;
use App\Feed\CurrentActor;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Shared plumbing for feed controllers: the resolved actor (never a direct
 * Auth::guard() call — feed-spec §2) and a gate bound to the acting identity —
 * the Actor when present, else an authenticated admin, else the guest (null),
 * so guest-allowed abilities (nullable policy params) evaluate correctly.
 */
abstract class FeedBaseController extends Controller
{
    public function __construct(protected readonly CurrentActor $currentActor)
    {
    }

    /**
     * The acting identity for authorization: Actor, admin User, or null guest.
     */
    protected function actingUser(): ?Authenticatable
    {
        return $this->currentActor->resolve() ?? Auth::guard('admin')->user();
    }

    /**
     * A Gate instance evaluating as the acting identity.
     */
    protected function gate(): GateContract
    {
        return Gate::forUser($this->actingUser());
    }
}
