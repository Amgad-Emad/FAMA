<?php

namespace App\Http\Requests\Feed;

use App\Rules\PostMediaFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validates a new note or media post (feed-spec §4): a post must carry body
 * or media (an empty note is invalid); 1–10 media items on media posts;
 * per-file caps in PostMediaFile. Skill ownership is validated in CreatePost
 * (shared by web + API + replay paths).
 */
class StorePostRequest extends FormRequest
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
            'post_type' => ['required', 'in:note,media'],
            'body' => ['nullable', 'string', 'max:'.config('feed.limits.body_max_chars')],
            'media' => ['array', 'max:'.config('feed.limits.media_per_post')],
            'media.*' => ['file', new PostMediaFile],
            'media_alt' => ['array'],
            'media_alt.*' => ['nullable', 'string', 'max:160'],
            'skill_ids' => ['array'],
            'skill_ids.*' => ['integer', 'exists:talent_types,id'],
        ];
    }

    /**
     * Cross-field rule: substance is required — body or media.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $hasBody = trim((string) $this->input('body')) !== '';
            $hasMedia = $this->file('media') !== null && $this->file('media') !== [];

            if (! $hasBody && ! $hasMedia) {
                $validator->errors()->add('body', __('A post needs a body or media.'));
            }

            if ($this->input('post_type') === 'media' && ! $hasMedia) {
                $validator->errors()->add('media', __('A media post needs at least one image or video.'));
            }
        });
    }
}
