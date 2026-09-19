<?php

namespace App\Http\Requests\Talent;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The talent's speciality picks under ONE skill (ADR-Y).
 *
 * Shape only. Whether each id belongs to that skill — the rule that keeps the
 * vocabulary controlled — is SkillsService::setSpecialities' job, so the web
 * picker and the API cannot drift on it. An empty array is valid and clears the
 * skill's claims.
 */
class UpdateSpecialitiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ownership is the controller's guard (talent guard + own profile).
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'speciality_ids' => ['present', 'array'],
            'speciality_ids.*' => ['integer', 'exists:specialities,id'],
        ];
    }

    /**
     * The ordered ids as integers — the order is what the profile renders.
     *
     * @return list<int>
     */
    public function specialityIds(): array
    {
        return array_values(array_map('intval', $this->validated('speciality_ids')));
    }
}
