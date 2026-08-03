<?php

namespace App\Http\Requests\Feed;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a new comment/reply body (feed-spec §4 limits). Authorization is
 * the require.actor middleware + CommentPolicy — the request only shapes
 * input.
 */
class StoreCommentRequest extends FormRequest
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
            'body' => ['required', 'string', 'max:'.config('feed.limits.body_max_chars')],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ];
    }
}
