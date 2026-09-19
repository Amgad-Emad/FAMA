<?php

namespace App\Http\Requests\Talent;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The talent's social handles as ONE ordered list (ADR-Z). A whole-list replace,
 * so `present` (not `required`) — an empty array legitimately clears them.
 *
 * `distinct` on the platform enforces the (talent_id, platform) unique index at
 * the request boundary, where it can be shown as a field error, rather than
 * letting it surface as a database exception.
 */
class UpdateSocialHandlesRequest extends FormRequest
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
            'handles' => ['present', 'array', 'max:9'],
            'handles.*.platform' => [
                'required', 'distinct',
                'in:instagram,tiktok,x,linkedin,youtube,behance,vimeo,website,other',
            ],
            'handles.*.handle' => ['required', 'string', 'max:120'],
            'handles.*.url' => ['required', 'url', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'handles.*.platform.distinct' => __('You can only add one link per platform.'),
        ];
    }
}
