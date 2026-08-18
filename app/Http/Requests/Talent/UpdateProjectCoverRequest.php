<?php

namespace App\Http\Requests\Talent;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Project cover image upload — one image into the project's single-file `cover`
 * collection. Validated at the boundary (type + size) like every other upload.
 */
class UpdateProjectCoverRequest extends FormRequest
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
            'cover' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'], // 10 MB
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['cover' => __('cover image')];
    }
}
