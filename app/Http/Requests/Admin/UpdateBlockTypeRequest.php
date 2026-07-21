<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates an edit to a block-type catalog entry. `key`/`content_source`
 * immutability once the type is in use is a domain rule enforced by
 * BlockCatalogService (422), not a validation rule — it depends on live
 * profile_blocks state.
 */
class UpdateBlockTypeRequest extends FormRequest
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
            'key' => ['sometimes', 'string', 'max:64', 'alpha_dash', Rule::unique('block_types', 'key')->ignore($this->route('blockType'))],
            'name' => ['sometimes', 'array'],
            'name.en' => ['required_with:name', 'string', 'max:255'],
            'name.ar' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'description.en' => ['nullable', 'string', 'max:2000'],
            'description.ar' => ['nullable', 'string', 'max:2000'],
            'icon' => ['nullable', 'string', 'max:255'],
            'availability' => ['sometimes', Rule::in(['universal', 'by_category', 'by_type'])],
            'categories' => ['required_if:availability,by_category', 'array'],
            'categories.*' => [Rule::in(['model', 'crew', 'creative'])],
            'talent_type_ids' => ['required_if:availability,by_type', 'array'],
            'talent_type_ids.*' => ['integer', Rule::exists('talent_types', 'id')],
            'content_source' => ['sometimes', Rule::in(['inline', 'table'])],
            'default_layout' => ['nullable', Rule::in(['grid', 'carousel', 'list', 'masonry'])],
            'is_active' => ['boolean'],
            'is_repeatable' => ['boolean'],
            'settings_schema' => ['nullable', 'string', 'json'],
        ];
    }
}
