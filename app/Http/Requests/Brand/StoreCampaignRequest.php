<?php

namespace App\Http\Requests\Brand;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a new campaign. Roles arrive as a list of {talent_type_id, quantity}
 * and are folded into the CampaignService's [type_id => quantity] map by the
 * controller. Authorization is handled by the brand guard.
 */
class StoreCampaignRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(['campaign', 'shoot'])],
            'description' => ['nullable', 'array'],
            'description.en' => ['nullable', 'string', 'max:2000'],
            'description.ar' => ['nullable', 'string', 'max:2000'],
            'budget_min' => ['nullable', 'numeric', 'min:0'],
            'budget_max' => ['nullable', 'numeric', 'min:0', 'gte:budget_min'],
            'currency' => ['nullable', 'string', 'size:3'],
            'location_city' => ['nullable', 'string', 'max:255'],
            'location_country' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_public' => ['boolean'],
            'positions_count' => ['nullable', 'integer', 'min:0'],
            'roles' => ['array'],
            'roles.*.talent_type_id' => ['required', 'integer', 'exists:talent_types,id'],
            'roles.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
