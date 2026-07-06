<?php

namespace App\Http\Requests\Talent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAvailabilityRequest extends FormRequest
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
            'availability_status' => ['required', Rule::in(['available', 'booked', 'unavailable'])],
            'willing_to_travel' => ['boolean'],
            'travel_regions' => ['nullable', 'array'],
            'travel_regions.*' => ['string', 'max:100'],
            'rate_tier' => ['nullable', Rule::in(['emerging', 'established', 'premium', 'elite'])],
        ];
    }
}
