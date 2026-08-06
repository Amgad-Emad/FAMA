<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a new / edited contract template (ADR-V). Authorization is enforced by
 * the `can:manage-flows` route middleware + the builder service. `clauses` arrives
 * as a real array (http.js posts JSON), each entry a {label, body} pair.
 */
class StoreContractTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * On PATCH the name may be omitted (partial save), so it is `sometimes`.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'applies_to' => ['nullable', Rule::in(['model', 'crew', 'creative', 'all'])],
            'deposit_pct' => ['nullable', 'integer', 'between:0,100'],
            'deposit_holder' => ['nullable', 'string', 'max:120'],
            'cancellation_forfeit_pct' => ['nullable', 'integer', 'between:0,100'],
            'revision_rounds' => ['nullable', 'integer', 'between:0,50'],
            'preview_currency' => ['nullable', 'string', 'size:3'],
            'intro' => ['nullable', 'string', 'max:500'],
            'clauses' => ['nullable', 'array', 'max:40'],
            'clauses.*.label' => ['nullable', 'string', 'max:160'],
            'clauses.*.body' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
