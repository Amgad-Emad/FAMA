<?php

namespace App\Http\Requests\Talent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FillBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Block names are admin-governed (block_types.name via /admin/blocks) —
     * `title` is deliberately absent so a talent-submitted title never reaches
     * the service through validated().
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'content' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
            'layout' => ['nullable', Rule::in(['grid', 'carousel', 'list', 'masonry'])],
        ];
    }
}
