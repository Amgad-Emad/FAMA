<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Public brand sign-up. Creates the `brands` account with its display name and
 * credentials; the account starts incomplete (is_complete = false) so the
 * 6-step onboarding wizard gates the discovery feed, and unpublished until the
 * brand chooses to go public. The slug is derived from the name here (the Brand
 * model has no auto-slug hook).
 */
class RegisterBrandRequest extends FormRequest
{
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:brands,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ];
    }

    /**
     * The label to store on the issued token.
     */
    public function deviceName(): string
    {
        $name = trim((string) $this->input('device_name', ''));

        return $name !== '' ? $name : 'api';
    }
}
