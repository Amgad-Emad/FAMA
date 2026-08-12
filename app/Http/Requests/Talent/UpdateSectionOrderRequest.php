<?php

namespace App\Http\Requests\Talent;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Section-order update from the editor's pill bar: the universal section's
 * index among the skill sections (0 = above the skill tabs on the public
 * profile, anything higher = below them).
 */
class UpdateSectionOrderRequest extends FormRequest
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
            'position' => ['required', 'integer', 'min:0', 'max:50'],
        ];
    }

    public function position(): int
    {
        return (int) $this->validated('position');
    }
}
