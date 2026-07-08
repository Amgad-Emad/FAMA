<?php

namespace App\Http\Controllers\Admin;

use App\Http\Resources\AdminUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

/**
 * Admin staff management (Phase 3B UI) — create/edit admins and assign
 * admin-guard roles. `can:manage-users` gates the routes; every change is
 * audited.
 */
class AdminUserController extends AdminController
{
    public function index(): View
    {
        return view('admin.users.index', [
            'roles' => Role::query()->where('guard_name', 'admin')->pluck('name'),
        ]);
    }

    public function data(): JsonResponse
    {
        $paginator = User::query()->with('roles')->latest()->paginate(20);

        return response()->paginated($paginator, AdminUserResource::collection($paginator->getCollection()));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'locale' => ['nullable', 'in:en,ar'],
            'roles' => ['array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'locale' => $data['locale'] ?? 'en',
            'is_active' => true,
        ]);
        $user->syncRoles($data['roles'] ?? []);

        activity('admin_users')->causedBy($this->admin())->performedOn($user)->log('admin_user.created');

        return response()->success(['id' => $user->id], __('Admin created.'), status: 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'locale' => ['nullable', 'in:en,ar'],
            'is_active' => ['boolean'],
        ]);

        $user->update($data);

        activity('admin_users')->causedBy($this->admin())->performedOn($user)->log('admin_user.updated');

        return response()->success(null, __('Admin updated.'));
    }

    public function syncRoles(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'roles' => ['array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $user->syncRoles($data['roles'] ?? []);

        activity('admin_users')->causedBy($this->admin())->performedOn($user)
            ->withProperties(['roles' => $data['roles'] ?? []])->log('admin_user.roles_synced');

        return response()->success(['roles' => $user->getRoleNames()], __('Roles updated.'));
    }

    public function destroy(User $user): JsonResponse
    {
        abort_if($user->getKey() === $this->admin()->getKey(), 422, __('You cannot remove your own account.'));

        $user->delete();
        activity('admin_users')->causedBy($this->admin())->performedOn($user)->log('admin_user.deleted');

        return response()->success(null, __('Admin removed.'));
    }
}
