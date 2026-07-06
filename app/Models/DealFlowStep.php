<?php

namespace App\Models;

use Database\Factories\DealFlowStepFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Deal flow step (schema-master §3) — one step in a flow template. `actor` says
 * who must act, `step_type` selects the StepHandler, `settings` holds per-step
 * config (required fields, payment %, contract template id).
 */
class DealFlowStep extends Model
{
    /** @use HasFactory<DealFlowStepFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'deal_flow_id', 'key', 'name', 'instructions', 'actor', 'step_type',
        'position', 'is_required', 'is_skippable', 'settings',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_required' => 'boolean',
            'is_skippable' => 'boolean',
            'settings' => 'array',
        ];
    }

    /**
     * @return BelongsTo<DealFlow, $this>
     */
    public function flow(): BelongsTo
    {
        return $this->belongsTo(DealFlow::class, 'deal_flow_id');
    }
}
