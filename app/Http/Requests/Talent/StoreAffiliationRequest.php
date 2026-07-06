<?php

namespace App\Http\Requests\Talent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAffiliationRequest extends FormRequest
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
            'agency_name' => ['required', 'string', 'max:255'],
            'agency_url' => ['nullable', 'url', 'max:255'],
            'representation_type' => ['required', Rule::in(['exclusive', 'non_exclusive', 'mother_agency', 'freelance'])],
            'region' => ['nullable', 'string', 'max:255'],
        ];
    }
}
