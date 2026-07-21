<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a new / edited contract-flow template. Authorization is enforced by the
 * `can:manage-flows` route middleware + the builder service.
 */
class StoreFlowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'applies_to' => ['nullable', Rule::in(['model', 'crew', 'creative', 'all'])],
        ];
    }
}
