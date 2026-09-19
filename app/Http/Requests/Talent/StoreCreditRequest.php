<?php

namespace App\Http\Requests\Talent;

use Illuminate\Foundation\Http\FormRequest;

/**
 * One collaborator credit (ADR-AA): person + skill + role.
 *
 * Exactly one of `talent_id` (someone on Fama, who must confirm) or `name`
 * (someone who is not, credited display-only) — enforced with
 * `required_without`, so the form can show it as a field error. That a
 * collaborator is not the project's own owner is the service's rule.
 *
 * `role` is REQUIRED and is the whole reason a credit is more than a name:
 * skill says what someone can do, role says what they did HERE.
 */
class StoreCreditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // the talent guard + project ownership gate the route
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'talent_id' => ['nullable', 'required_without:name', 'integer', 'exists:talents,id'],
            'name' => ['nullable', 'required_without:talent_id', 'string', 'max:120'],
            'talent_type_id' => ['required', 'integer', 'exists:talent_types,id'],
            'role' => ['required', 'string', 'max:80'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'talent_id.required_without' => __('Name the collaborator, or pick one from Fama.'),
            'name.required_without' => __('Name the collaborator, or pick one from Fama.'),
            'talent_type_id.required' => __('Pick the skill they were credited in.'),
            'role.required' => __('Say what they did on this project.'),
        ];
    }
}
