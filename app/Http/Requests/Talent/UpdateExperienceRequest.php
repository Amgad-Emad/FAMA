<?php

namespace App\Http\Requests\Talent;

use App\Support\Experience;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Experience roles (ADR-AK) — the whole list from the Profile structure draft.
 * A role needs a job title, a company and the year it started (the prototype's
 * rules); an end date can't come before the start.
 */
class UpdateExperienceRequest extends FormRequest
{
    /**
     * The talent guard already gates the route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $year = (int) now()->year;

        return [
            'roles' => ['present', 'array', 'max:50'],
            'roles.*.id' => ['nullable', 'integer'],
            'roles.*.t' => ['required', 'string', 'max:80'],
            'roles.*.n' => ['required', 'string', 'max:80'],
            'roles.*.et' => ['nullable', Rule::in(Experience::EMPLOYMENT)],
            'roles.*.loc' => ['nullable', 'string', 'max:60'],
            'roles.*.lt' => ['nullable', Rule::in(Experience::LOCATION_TYPES)],
            'roles.*.cur' => ['boolean'],
            'roles.*.sm' => ['nullable', 'integer', 'between:0,12'],
            'roles.*.sy' => ['required', 'integer', 'between:1950,'.$year],
            'roles.*.em' => ['nullable', 'integer', 'between:0,12'],
            'roles.*.ey' => ['nullable', 'integer', 'between:0,'.$year],
            'roles.*.hl' => ['nullable', 'string', 'max:2000'],
            'roles.*.sk' => ['nullable', 'array'],
            'roles.*.sk.*' => ['integer', 'exists:talent_types,id'],
        ];
    }

    /**
     * The end date can't be before the start date.
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach ((array) $this->input('roles', []) as $i => $r) {
                if (! empty($r['cur']) || empty($r['ey']) || empty($r['sy'])) {
                    continue;
                }
                if (((int) $r['ey']) * 12 + ((int) ($r['em'] ?? 0) ?: 12) < ((int) $r['sy']) * 12 + ((int) ($r['sm'] ?? 0) ?: 1)) {
                    $validator->errors()->add("roles.$i.ey", __("The end date can't be before the start date."));
                }
            }
        }];
    }

    /**
     * Messages in the prototype's words.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'roles.*.t.required' => __('Add this to save the role.'),
            'roles.*.n.required' => __('Add this to save the role.'),
            'roles.*.sy.required' => __('Add the year you started.'),
        ];
    }
}
