<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a new block-type catalog entry. Authorization is enforced by the
 * `can:manage-blocks` route middleware + BlockCatalogService.
 * `settings_schema` arrives as a raw JSON string from the editor textarea and
 * must be well-formed (the `json` rule); the controller decodes it.
 */
class StoreBlockTypeRequest extends FormRequest
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
            'key' => ['required', 'string', 'max:64', 'alpha_dash', Rule::unique('block_types', 'key')],
            'name' => ['required', 'array'],
            'name.en' => ['required', 'string', 'max:255'],
            'name.ar' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'description.en' => ['nullable', 'string', 'max:2000'],
            'description.ar' => ['nullable', 'string', 'max:2000'],
            'icon' => ['nullable', 'string', 'max:255'],
            'availability' => ['required', Rule::in(['universal', 'by_category', 'by_type'])],
            'categories' => ['required_if:availability,by_category', 'array'],
            'categories.*' => [Rule::in(['model', 'crew', 'creative'])],
            'talent_type_ids' => ['required_if:availability,by_type', 'array'],
            'talent_type_ids.*' => ['integer', Rule::exists('talent_types', 'id')],
            'content_source' => ['required', Rule::in(['inline', 'table'])],
            'default_layout' => ['nullable', Rule::in(['grid', 'carousel', 'list', 'masonry'])],
            'is_active' => ['boolean'],
            'is_repeatable' => ['boolean'],
            'settings_schema' => ['nullable', 'string', 'json'],
        ];
    }
}
