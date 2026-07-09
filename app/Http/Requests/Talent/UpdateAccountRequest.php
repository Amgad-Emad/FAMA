<?php

namespace App\Http\Requests\Talent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
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
        // Resolve the talent from the web (talent) guard or the API (sanctum) token.
        $talentId = $this->user('talent')?->getKey() ?? $this->user('sanctum')?->getKey();

        return [
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('talents', 'slug')->ignore($talentId)],
            'meta' => ['nullable', 'array'],
        ];
    }
}
