<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a live-preview render request for the contract-template builder
 * (ADR-V). Everything is nullable so a partial, in-progress draft still previews;
 * `sample` carries the fictional negotiation inputs that drive the preview.
 */
class PreviewContractTemplateRequest extends FormRequest
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
            'deposit_pct' => ['nullable', 'integer', 'between:0,100'],
            'deposit_holder' => ['nullable', 'string', 'max:120'],
            'cancellation_forfeit_pct' => ['nullable', 'integer', 'between:0,100'],
            'revision_rounds' => ['nullable', 'integer', 'between:0,50'],
            'preview_currency' => ['nullable', 'string', 'max:8'],
            'intro' => ['nullable', 'string', 'max:500'],
            'clauses' => ['nullable', 'array', 'max:40'],
            'clauses.*.label' => ['nullable', 'string', 'max:160'],
            'clauses.*.body' => ['nullable', 'string', 'max:2000'],
            'sample' => ['nullable', 'array'],
            'sample.talent' => ['nullable', 'string', 'max:160'],
            'sample.brand' => ['nullable', 'string', 'max:160'],
            'sample.scope' => ['nullable', 'string', 'max:500'],
            'sample.timeline' => ['nullable', 'string', 'max:200'],
            'sample.total' => ['nullable', 'numeric', 'min:0'],
            'sample.currency' => ['nullable', 'string', 'max:8'],
            'sample.exception' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
