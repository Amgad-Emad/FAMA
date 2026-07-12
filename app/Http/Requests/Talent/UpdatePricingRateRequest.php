<?php

namespace App\Http\Requests\Talent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Pricing rate (ADR-N). The three fields are all-or-nothing: filling any one makes
 * the other two required (`required_with`), so a rate is either complete or absent.
 */
class UpdatePricingRateRequest extends FormRequest
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
            'rate_unit' => ['nullable', 'required_with:rate_amount,rate_currency', Rule::in(['project', 'day', 'hour'])],
            'rate_amount' => ['nullable', 'required_with:rate_unit,rate_currency', 'numeric', 'min:0'],
            'rate_currency' => ['nullable', 'required_with:rate_unit,rate_amount', 'string', 'size:3', 'alpha'],
        ];
    }
}
