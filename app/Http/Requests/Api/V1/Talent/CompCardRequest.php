<?php

namespace App\Http\Requests\Api\V1\Talent;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Comp-card (model stats) upsert. All fields optional — a talent fills what
 * applies. Ownership is enforced by the controller (the card is the token's
 * talent's 1:1 record).
 */
class CompCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'height_cm' => ['nullable', 'integer', 'min:0', 'max:300'],
            'bust_cm' => ['nullable', 'integer', 'min:0', 'max:300'],
            'waist_cm' => ['nullable', 'integer', 'min:0', 'max:300'],
            'hips_cm' => ['nullable', 'integer', 'min:0', 'max:300'],
            'shoe_size' => ['nullable', 'string', 'max:20'],
            'dress_size' => ['nullable', 'string', 'max:20'],
            'hair_color' => ['nullable', 'string', 'max:50'],
            'eye_color' => ['nullable', 'string', 'max:50'],
            'skin_tone' => ['nullable', 'string', 'max:50'],
            'measurements' => ['nullable', 'array'],
        ];
    }
}
