<?php

namespace App\Http\Controllers\Admin;

use App\Http\Resources\AdminUserResource;
use App\Models\User;
use App\Services\AccountCreationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

/**
 * Admin staff + account management (Phase 3B UI) — create/edit admins, assign
 * admin-guard roles, and provision talent/brand accounts on a user's behalf.
 * `can:manage-users` gates the routes; every change is audited. The LIST is
 * admins only (brand/talent land in the moderation queues).
 */
class AdminUserController extends AdminController
{
    public function __construct(private readonly AccountCreationService $accounts) {}

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

    /**
     * Create an account of the chosen type. Admins get roles + appear in this
     * list; talent/brand are provisioned via the shared service and surface in
     * the moderation queues. Email uniqueness is scoped to the target table,
     * and roles are only accepted for the admin type.
     */
    public function store(Request $request): JsonResponse
    {
        $type = $request->input('account_type', 'admin');
        $emailTable = match ($type) {
            'brand' => 'brands',
            'talent' => 'talents',
            default => 'users',
        };

        $data = $request->validate([
            'account_type' => ['required', Rule::in(['admin', 'brand', 'talent'])],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique($emailTable, 'email')],
            'password' => ['required', 'string', 'min:8'],
            'locale' => ['nullable', 'in:en,ar'],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')->where('guard_name', 'admin')],
        ]);

        $account = match ($data['account_type']) {
            'brand' => $this->accounts->createBrand($data),
            'talent' => $this->accounts->createTalent($data),
            default => $this->accounts->createAdmin($data),
        };

        activity('admin_users')->causedBy($this->admin())->performedOn($account)
            ->withProperties(['account_type' => $data['account_type']])
            ->log('account.created');

        $message = match ($data['account_type']) {
            'brand' => __('Brand account created.'),
            'talent' => __('Talent account created.'),
            default => __('Admin created.'),
        };

        return response()->success([
            'id' => $account->id,
            'account_type' => $data['account_type'],
        ], $message, status: 201);
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
