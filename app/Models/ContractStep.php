<?php

namespace App\Models;

use App\States\ContractStep\ContractStepState;
use Database\Factories\ContractStepFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\ModelStates\HasStates;

/**
 * Contract step (schema-master §3) — the per-contract snapshot of a flow step and the
 * unit of progress. `status` is a state machine; `payload` captures what the
 * step recorded (quote amount, brief answers, upload references). `completed_by`
 * is a polymorphic actor (talents / brands / users).
 */
class ContractStep extends Model
{
    /** @use HasFactory<ContractStepFactory> */
    use HasFactory, HasStates;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'contract_id', 'flow_step_id', 'key', 'name', 'actor', 'step_type',
        'position', 'status', 'is_required', 'is_skippable', 'settings',
        'payload', 'completed_by_type', 'completed_by_id', 'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ContractStepState::class,
            'position' => 'integer',
            'is_required' => 'boolean',
            'is_skippable' => 'boolean',
            'settings' => 'array',
            'payload' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Contract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * @return BelongsTo<ContractFlowStep, $this>
     */
    public function flowStep(): BelongsTo
    {
        return $this->belongsTo(ContractFlowStep::class, 'flow_step_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function completedBy(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<ContractMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ContractMessage::class);
    }

    /**
     * Can the given role act on this step now? True when it is the current step
     * and the role matches the actor (or the step is open to `both`).
     */
    public function actionableBy(string $role): bool
    {
        return $this->status->isCurrent()
            && ($this->actor === $role || $this->actor === 'both');
    }
}
