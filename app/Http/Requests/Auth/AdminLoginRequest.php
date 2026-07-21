<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Staff login request for the dedicated /admin/login screen. Inherits the
 * shared authenticate() pipeline (rate limiting, single-active-identity) from
 * LoginRequest but pins the guard to `admin` — no role field is accepted, so
 * the public role-aware form and the staff console stay fully separate.
 */
class AdminLoginRequest extends LoginRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Always the admin guard — this endpoint serves staff only.
     */
    public function role(): UserRole
    {
        return UserRole::Admin;
    }
}
