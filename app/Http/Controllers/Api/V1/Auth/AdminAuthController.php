<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Requests\Api\V1\Auth\RegisterAdminRequest;
use App\Http\Resources\AdminUserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @group Admin authentication
 *
 * Token auth for the `admin` (staff) guard: login/logout/refresh/me plus
 * provisioning of new staff. Admin tokens carry the `admin` ability AND the
 * admin's granular spatie permissions (manage-flows, moderate-content, …) as
 * abilities, so future admin API routes can gate with `abilities:<permission>`.
 * There is deliberately no public admin sign-up — staff are provisioned by an
 * existing admin holding `manage-users`.
 */
class AdminAuthController extends AbstractAuthController
{
    protected function guard(): string
    {
        return 'admin';
    }

    protected function model(): string
    {
        return User::class;
    }

    protected function resource(Model $entity): JsonResource
    {
        return new AdminUserResource($entity->loadMissing('roles'));
    }

    /**
     * Admin tokens carry the guard ability plus the admin's effective spatie
     * permissions, mirroring what the same user can do on the web.
     *
     * @param  User  $entity
     * @return list<string>
     */
    protected function abilitiesFor(Model $entity): array
    {
        return array_values(array_unique([
            'admin',
            ...$entity->getAllPermissions()->pluck('name')->all(),
        ]));
    }

    /**
     * Provision an admin
     *
     * Create a new staff account with optional roles. Restricted to an
     * authenticated admin holding `manage-users`; audited with the acting admin
     * as causer. Returns the created admin (no token — the new admin logs in
     * themselves).
     *
     * @authenticated
     *
     * @response 201 scenario="Created" {"success":true,"data":{"id":2,"name":"Mod","email":"mod@fama.test","roles":["moderator"]},"message":"Admin created.","errors":null,"meta":null}
     */
    public function register(RegisterAdminRequest $request): JsonResponse
    {
        $admin = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'is_active' => true,
        ]);
        $admin->syncRoles($request->validated('roles') ?? []);

        activity('admin_users')
            ->causedBy($request->user())
            ->performedOn($admin)
            ->log('admin_user.created');

        return response()->success(
            new AdminUserResource($admin->load('roles')),
            __('Admin created.'),
            [],
            201,
        );
    }
}
