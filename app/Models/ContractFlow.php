<?php

namespace App\Models;

use App\States\ContractFlow\ContractFlowState;
use Database\Factories\ContractFlowFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\ModelStates\HasStates;

/**
 * Contract flow (schema-master §3) — a named, reusable, admin-authored step
 * sequence. Snapshotted into `contract_steps` at contract creation, so editing a flow
 * only affects future contracts. `applies_to` scopes it to a talent category.
 */
class ContractFlow extends Model
{
    /** @use HasFactory<ContractFlowFactory> */
    use HasFactory;

    use HasStates, LogsActivity;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name', 'slug', 'description', 'applies_to', 'is_active', 'is_default', 'status',
    ];

    /**
     * Audit every admin edit to a flow template (schema-master §3): a flow is
     * snapshotted into `contract_steps`, so a change here silently reshapes every
     * future contract — the `contract_flow` log is how that is traced back.
     * Dirty-only so an untouched save is not logged as a change.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('contract_flow')
            ->logOnly(['name', 'slug', 'description', 'applies_to', 'is_active', 'is_default', 'status'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'status' => ContractFlowState::class,
        ];
    }

    /**
     * The ordered steps of this flow.
     *
     * @return HasMany<ContractFlowStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(ContractFlowStep::class)->orderBy('position');
    }

    /**
     * Contracts running on this flow (the inverse of Contract::flow()) — the admin
     * flow console counts these to show each flow's usage.
     *
     * @return HasMany<Contract, $this>
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'contract_flow_id');
    }

    /**
     * @param  Builder<ContractFlow>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Resolve the flow to seed a contract from: the default active flow that applies
     * to the given category (or to all). Falls back to any default active flow.
     *
     * @param  Builder<ContractFlow>  $query
     */
    public function scopeApplicableTo(Builder $query, ?string $category): void
    {
        $query->where('is_active', true)
            ->when($category !== null, fn (Builder $q) => $q->whereIn('applies_to', [$category, 'all'])->orWhereNull('applies_to'))
            ->orderByDesc('is_default');
    }
}
