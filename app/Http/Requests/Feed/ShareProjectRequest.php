<?php

namespace App\Http\Requests\Feed;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a Project share (feed-spec §4, ADR-F2): an optional body plus
 * skill tags. Ownership + profile-published gates live in ShareProjectAsPost.
 */
class ShareProjectRequest extends FormRequest
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
            'body' => ['nullable', 'string', 'max:'.config('feed.limits.body_max_chars')],
            'skill_ids' => ['array'],
            'skill_ids.*' => ['integer', 'exists:talent_types,id'],
        ];
    }
}
