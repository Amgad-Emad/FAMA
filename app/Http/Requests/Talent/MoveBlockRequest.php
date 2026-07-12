<?php

namespace App\Http\Requests\Talent;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Move a block between scopes (ADR-Q): the target `talent_type_id` (a skill's tab)
 * or null = the universal / profile-level section.
 */
class MoveBlockRequest extends FormRequest
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
            'talent_type_id' => ['nullable', 'integer', 'exists:talent_types,id'],
        ];
    }
}
