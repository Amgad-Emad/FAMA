<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Credentials for a mobile-API token login (shared by all three guards). The
 * optional `device_name` labels the issued token so a user can see and revoke
 * per-device sessions ("Amgad's iPhone").
 */
class LoginApiRequest extends FormRequest
{
    /**
     * Auth throttling is enforced by the route middleware; the request itself is
     * always authorizable.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ];
    }

    /**
     * The label to store on the issued token (defaults to a generic API client).
     */
    public function deviceName(): string
    {
        $name = trim((string) $this->input('device_name', ''));

        return $name !== '' ? $name : 'api';
    }
}
