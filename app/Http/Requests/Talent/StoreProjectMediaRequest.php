<?php

namespace App\Http\Requests\Talent;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Uploaded work for a project's media gallery (ADR-AA) — images and video.
 *
 * Video is capped harder than stills because a project gallery is a portfolio,
 * not a delivery channel: anything longer belongs on a showreel host and comes
 * in as an embed.
 */
class StoreProjectMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // the talent guard + project ownership gate the route
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'max:12'],
            'files.*' => [
                'file',
                'mimes:jpg,jpeg,png,webp,mp4,mov,webm',
                'max:51200', // 50 MB
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'files.*.mimes' => __('Images (JPG, PNG, WebP) and video (MP4, MOV, WebM) only.'),
            'files.*.max' => __('Each file must be 50 MB or smaller.'),
        ];
    }
}
