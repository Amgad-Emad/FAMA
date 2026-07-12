<?php

namespace App\Http\Requests\Talent;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared payload for drag-reorder across the dashboard: an ordered list of ids.
 */
class ReorderRequest extends FormRequest
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
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['integer'],
            // Optional scope (block reorder is per-tab); ignored by other reorders.
            'talent_type_id' => ['nullable', 'integer', 'exists:talent_types,id'],
        ];
    }

    /**
     * @return list<int>
     */
    public function orderedIds(): array
    {
        return array_map('intval', $this->input('order', []));
    }
}
