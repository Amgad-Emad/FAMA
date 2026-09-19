<?php

namespace App\Http\Requests\Talent;

use Illuminate\Foundation\Http\FormRequest;

/**
 * One "willing to explore" direction (ADR-Z).
 *
 * Shape only. Note what is deliberately absent: any rule that the skill be one
 * the talent already holds. Wanting to move toward a discipline you do not yet
 * list is the point of the section. That the speciality belongs to the chosen
 * skill is `TalentInterestService::assertShape()`'s job.
 */
class StoreInterestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // the talent guard already gates the route
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'talent_type_id' => ['required', 'integer', 'exists:talent_types,id'],
            'speciality_id' => ['nullable', 'integer', 'exists:specialities,id'],
            'note' => ['nullable', 'string', 'max:160'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['talent_type_id.required' => __('Pick a skill for this direction.')];
    }
}
