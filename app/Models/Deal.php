<?php

namespace App\Models;

use App\States\Deal\DealState;
use Database\Factories\DealFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\ModelStates\HasStates;

/**
 * Deal (schema-master §3) — one brand ↔ talent engagement. `status` (a state
 * machine) mirrors whose turn it is; `current_step_id` points at the active
 * `deal_steps` row. Orchestrated exclusively through App\Services\DealService.
 */
class Deal extends Model
{
    /** @use HasFactory<DealFactory> */
    use HasFactory, HasStates, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'reference', 'brand_id', 'talent_id', 'service_id', 'deal_flow_id', 'campaign_id',
        'current_step_id', 'status', 'title', 'brief', 'agreed_amount',
        'currency', 'start_date', 'end_date', 'initiated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DealState::class,
            'agreed_amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * Build a human deal reference, e.g. FAMA-2026-0001.
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
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return BelongsTo<DealFlow, $this>
     */
    public function flow(): BelongsTo
    {
        return $this->belongsTo(DealFlow::class, 'deal_flow_id');
    }

    /**
     * The campaign this deal runs under, if any (ADR-F).
     *
     * @return BelongsTo<Campaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * The talent's review of the brand for this deal (at most one).
     *
     * @return HasOne<BrandReview, $this>
     */
    public function brandReview(): HasOne
    {
        return $this->hasOne(BrandReview::class);
    }

    /**
     * @return BelongsTo<DealStep, $this>
     */
    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(DealStep::class, 'current_step_id');
    }

    /**
     * @return HasMany<DealStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(DealStep::class)->orderBy('position');
    }

    /**
     * @return HasMany<DealMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(DealMessage::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * Deals belonging to a talent (inbox).
     *
     * @param  Builder<Deal>  $query
     */
    public function scopeForTalent(Builder $query, int $talentId): void
    {
        $query->where('talent_id', $talentId);
    }
}
