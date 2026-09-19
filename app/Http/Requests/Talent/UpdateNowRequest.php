<?php

namespace App\Http\Requests\Talent;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The NOW strip's two toggles (ADR-Z). Both optional so the editor can flip one
 * without restating the other — `TalentProfileService::updateNow()` only touches
 * the keys that are present, which is what keeps `availability_changed_at` from
 * being refreshed by an unrelated save.
 */
class UpdateNowRequest extends FormRequest
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
            'will_travel' => ['sometimes', 'boolean'],
            'is_available' => ['sometimes', 'boolean'],
        ];
    }
}
