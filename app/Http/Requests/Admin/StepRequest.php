<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a deal-flow step (add / edit). All fields optional on edit; `sometimes`
 * lets a partial update through.
 */
class StepRequest extends FormRequest
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
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'key' => [$required, 'string', 'max:64'],
            'name' => [$required, 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'actor' => [$required, Rule::in(['brand', 'talent', 'admin', 'system', 'both'])],
            'step_type' => [$required, Rule::in(['form', 'approval', 'upload', 'payment', 'contract', 'message', 'schedule', 'info'])],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_required' => ['boolean'],
            'is_skippable' => ['boolean'],
            'settings' => ['nullable', 'array'],
        ];
    }
}
