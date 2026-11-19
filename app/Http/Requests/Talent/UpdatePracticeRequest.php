<?php

namespace App\Http\Requests\Talent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * About your practice (ADR-AG). The whole draft arrives as `fields`, keyed by
 * field id. Shape and option checks live in App\Support\PracticeFields::clean()
 * (one home); this request only turns the mistakes a person can actually make
 * in the editor — a number out of range, a playing age under five years wide —
 * into a readable 422 instead of a silent drop.
 */
class UpdatePracticeRequest extends FormRequest
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
        return [
            'fields' => ['present', 'array'],
            'fields.height' => ['nullable', 'integer', 'between:120,220'],
            'fields.measure.*' => ['nullable', 'integer', 'between:20,250'],
            'fields.agerange.min' => ['nullable', 'integer', 'between:5,90'],
            'fields.agerange.max' => ['nullable', 'integer', 'between:5,90'],
            'fields.licence.exp' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    /**
     * A playing age must be at least five years wide.
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $range = (array) $this->input('fields.agerange', []);
            if (! empty($range['min']) && ! empty($range['max']) && ((int) $range['max'] - (int) $range['min']) < 5) {
                $validator->errors()->add('fields.agerange', __('Make the playing age at least five years wide.'));
            }
        }];
    }

    /**
     * Friendly names for the messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'fields.height' => __('height'),
            'fields.measure.*' => __('measurement'),
            'fields.agerange.min' => __('playing age'),
            'fields.agerange.max' => __('playing age'),
            'fields.licence.exp' => __('licence expiry'),
        ];
    }
}
