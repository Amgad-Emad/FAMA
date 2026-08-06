<?php

namespace App\Http\Resources;

use App\Models\ContractTemplate;
use Illuminate\Http\Request;

/**
 * @mixin ContractTemplate
 */
class ContractTemplateResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'applies_to' => $this->applies_to,
            'is_active' => (bool) $this->is_active,
            'is_default' => (bool) $this->is_default,
            'deposit_pct' => (int) $this->deposit_pct,
            'deposit_holder' => $this->deposit_holder,
            'cancellation_forfeit_pct' => (int) $this->cancellation_forfeit_pct,
            'revision_rounds' => (int) $this->revision_rounds,
            'preview_currency' => $this->preview_currency,
            'intro' => $this->intro,
            'clauses' => $this->clauses ?? [],
            'flows_count' => $this->whenCounted('flows'),
        ];
    }
}
