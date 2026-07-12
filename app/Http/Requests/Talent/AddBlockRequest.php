<?php

namespace App\Http\Requests\Talent;

use Illuminate\Foundation\Http\FormRequest;

class AddBlockRequest extends FormRequest
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
            'block_type_id' => ['required', 'integer', 'exists:block_types,id'],
            // The scope to add into: a skill's talent_type_id, or null = universal.
            'talent_type_id' => ['nullable', 'integer', 'exists:talent_types,id'],
        ];
    }
}
