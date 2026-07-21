<?php

namespace App\Models;

use Database\Factories\ContractFlowStepFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Contract flow step (schema-master §3) — one step in a flow template. `actor` says
 * who must act, `step_type` selects the StepHandler, `settings` holds per-step
 * config (required fields, payment %, contract template id). Admin edits are
 * audited via spatie/laravel-activitylog.
 */
class ContractFlowStep extends Model
{
    /** @use HasFactory<ContractFlowStepFactory> */
    use HasFactory;

    use LogsActivity;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'contract_flow_id', 'key', 'name', 'instructions', 'actor', 'step_type',
        'position', 'is_required', 'is_skippable', 'settings',
    ];

    /**
     * Audit admin edits to a step template. Shares the `contract_flow` log name with
     * the parent flow so the console reads one chronological trail per flow rather
     * than two interleaved ones.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('contract_flow')
            ->logOnly(['key', 'name', 'instructions', 'actor', 'step_type', 'position', 'is_required', 'is_skippable', 'settings'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

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
     * @return BelongsTo<ContractFlow, $this>
     */
    public function flow(): BelongsTo
    {
        return $this->belongsTo(ContractFlow::class, 'contract_flow_id');
    }
}
