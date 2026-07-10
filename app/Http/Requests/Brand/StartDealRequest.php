<?php

namespace App\Http\Requests\Brand;

use App\Models\Brand;
use App\Models\Talent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * "Start a deal" input, shared by the web dashboard and the mobile API. Validates
 * shape + ownership (service belongs to the talent, campaign belongs to the
 * brand); the participant guards (talent published + bookable, brand complete)
 * and flow resolution live in DealService (surfaced as 422). The brand is the
 * authenticated entity (brand session guard or sanctum token).
 */
class StartDealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->brand() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'talent_id' => ['required', 'integer', 'exists:talents,id'],
            'service_id' => ['nullable', 'integer', Rule::exists('services', 'id')->where('talent_id', $this->input('talent_id'))],
            'deal_flow_id' => ['nullable', 'integer', 'exists:deal_flows,id'],
            'campaign_id' => ['nullable', 'integer', Rule::exists('campaigns', 'id')->where('brand_id', $this->brand()?->getKey())],
            'brief' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * The authenticated brand (web guard or API token).
     */
    public function brand(): ?Brand
    {
        return $this->user('brand') ?? $this->user('sanctum');
    }

    /**
     * The resolved payload handed to DealService::startBrandDeal (the Talent
     * model + optional flow/service/campaign/brief).
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'talent' => Talent::findOrFail($this->integer('talent_id')),
            'service_id' => $this->input('service_id'),
            'deal_flow_id' => $this->input('deal_flow_id'),
            'campaign_id' => $this->input('campaign_id'),
            'brief' => $this->input('brief'),
        ];
    }
}
