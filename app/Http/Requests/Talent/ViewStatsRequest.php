<?php

namespace App\Http\Requests\Talent;

use App\Services\ProfileViewStatsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Range filter for the dashboard views chart. `range` is one of the fixed
 * presets ('all' reaches back to the profile's creation date); it defaults
 * to '30d' when absent.
 */
class ViewStatsRequest extends FormRequest
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
            'range' => ['sometimes', 'string', Rule::in(ProfileViewStatsService::RANGES)],
        ];
    }

    public function range(): string
    {
        return $this->validated('range', '30d');
    }
}
