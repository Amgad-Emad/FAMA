<?php

namespace App\Http\Requests\Feed;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a content report (feed-spec §10): a reason from the fixed enum
 * plus an optional note. Once-per-target authorization is PostReportPolicy.
 */
class StoreReportRequest extends FormRequest
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
            'reason' => ['required', 'in:'.implode(',', config('feed.report_reasons'))],
            // "Something else" needs the reporter's own words (5AF).
            'note' => ['required_if:reason,other', 'nullable', 'string', 'max:500'],
        ];
    }
}
