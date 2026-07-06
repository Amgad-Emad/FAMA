<?php

namespace App\Http\Requests\Talent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCoreProfileRequest extends FormRequest
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
        $talentId = $this->user('talent')?->getKey();

        return [
            'display_name' => ['nullable', 'string', 'max:255'],
            'headline' => ['nullable', 'array'],
            'headline.en' => ['nullable', 'string', 'max:255'],
            'headline.ar' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'array'],
            'bio.en' => ['nullable', 'string', 'max:5000'],
            'bio.ar' => ['nullable', 'string', 'max:5000'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('talents', 'slug')->ignore($talentId)],
            'base_city' => ['nullable', 'string', 'max:255'],
            'base_country' => ['nullable', 'string', 'max:255'],
            'booking_type' => ['nullable', Rule::in(['email', 'calendar', 'form', 'external'])],
            'booking_value' => ['nullable', 'string', 'max:255'],
            'willing_to_travel' => ['boolean'],
            'travel_regions' => ['nullable', 'array'],
            'travel_regions.*' => ['string', 'max:100'],
            'rate_tier' => ['nullable', Rule::in(['emerging', 'established', 'premium', 'elite'])],
        ];
    }
}
