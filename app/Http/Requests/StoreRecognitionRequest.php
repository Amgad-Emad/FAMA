<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A peer vouch (ADR-Z). `relationship` is REQUIRED and carries a real minimum:
 * "worked together" tells a reader nothing, and a recognition without a stated
 * relationship is indistinguishable from generic praise — which is precisely the
 * failure mode the section is designed to avoid.
 */
class StoreRecognitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // the route is gated to a signed-in talent
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'relationship' => ['required', 'string', 'min:8', 'max:160'],
            'body' => ['required', 'string', 'min:40', 'max:1500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'relationship.required' => __('Say how you know them.'),
            'relationship.min' => __('Be specific about how you know them — "worked together" does not tell anyone much.'),
            'body.min' => __('A recognition needs a little more than a line to be worth reading.'),
        ];
    }
}
