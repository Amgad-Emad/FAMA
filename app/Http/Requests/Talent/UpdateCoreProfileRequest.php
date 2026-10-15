<?php

namespace App\Http\Requests\Talent;

use App\Support\Locations;
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
            // Taste and point of view, kept apart from the bio (ADR-Z).
            'worldview' => ['nullable', 'array'],
            'worldview.en' => ['nullable', 'string', 'max:2000'],
            'worldview.ar' => ['nullable', 'string', 'max:2000'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('talents', 'slug')->ignore($talentId)],
            'base_city' => ['nullable', 'string', 'max:255'],
            // A country from the drop list. A legacy free-text value the talent
            // already has may ride along unchanged (it is canonicalised on save).
            'base_country' => ['nullable', 'string', 'max:255', function (string $attribute, mixed $value, \Closure $fail): void {
                if (Locations::codeFor($value) === null && $value !== $this->user('talent')?->base_country) {
                    $fail(__('Choose a country from the list.'));
                }
            }],
        ];
    }

    /**
     * Store the canonical country name ("UAE" → "United Arab Emirates") so
     * discovery filters match one spelling.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('base_country')) && $this->input('base_country') !== '') {
            $this->merge(['base_country' => Locations::canonicalName($this->input('base_country'))]);
        }
    }

    /**
     * The public `slug` is surfaced as "Username" in the UI, so validation
     * messages read naturally (e.g. "The username has already been taken.").
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['slug' => __('username')];
    }
}
