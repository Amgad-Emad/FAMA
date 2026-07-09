<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

/**
 * Provision a new admin (staff) account over the API. Unlike talent/brand
 * sign-up, admin accounts are NEVER self-registered — an open admin-signup
 * endpoint would be a privilege-escalation hole. This request therefore
 * authorizes only an already-authenticated admin holding `manage-users`
 * (mirroring the web AdminUserController), and the route is additionally gated
 * by the `abilities:manage-users` token scope.
 */
class RegisterAdminRequest extends FormRequest
{
    /**
     * Only an authenticated admin with manage-users may create staff.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('manage-users');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', Rule::exists(Role::class, 'name')->where('guard_name', 'admin')],
        ];
    }
}
