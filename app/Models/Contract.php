<?php

namespace App\Models;

use App\States\Contract\ContractState;
use Database\Factories\ContractFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\ModelStates\HasStates;

/**
 * Contract (schema-master §3) — one brand ↔ talent engagement. `status` (a state
 * machine) mirrors whose turn it is; `current_step_id` points at the active
 * `contract_steps` row. Orchestrated exclusively through App\Services\ContractService.
 */
class Contract extends Model
{
    /** @use HasFactory<ContractFactory> */
    use HasFactory, HasStates, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'reference', 'brand_id', 'talent_id', 'contract_flow_id',
        'current_step_id', 'status', 'title', 'brief', 'agreed_amount',
        'currency', 'start_date', 'end_date', 'initiated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ContractState::class,
            'agreed_amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * Build a human contract reference, e.g. FAMA-2026-0001.
     */
    public static function makeReference(int $year, int $sequence): string
    {
        return sprintf('FAMA-%d-%04d', $year, $sequence);
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return BelongsTo<Talent, $this>
     */
    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class);
    }


    /**
     * @return BelongsTo<ContractFlow, $this>
     */
    public function flow(): BelongsTo
    {
        return $this->belongsTo(ContractFlow::class, 'contract_flow_id');
    }

    /**
     * The project this contract runs under, if any (ADR-F).
     *
     * @return BelongsTo<BrandProject, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(BrandProject::class, 'brand_project_id');
    }

    /**
     * The talent's review of the brand for this contract (at most one).
     *
     * @return HasOne<BrandReview, $this>
     */
    public function brandReview(): HasOne
    {
        return $this->hasOne(BrandReview::class);
    }

    /**
     * @return BelongsTo<ContractStep, $this>
     */
    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(ContractStep::class, 'current_step_id');
    }

    /**
     * @return HasMany<ContractStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(ContractStep::class)->orderBy('position');
    }

    /**
     * @return HasMany<ContractMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ContractMessage::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * Contracts belonging to a talent (inbox).
     *
     * @param  Builder<Contract>  $query
     */
    public function scopeForTalent(Builder $query, int $talentId): void
    {
        $query->where('talent_id', $talentId);
    }
}
