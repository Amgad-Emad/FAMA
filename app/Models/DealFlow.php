<?php

namespace App\Models;

use Database\Factories\DealFlowFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Deal flow (schema-master §3) — a named, reusable, admin-authored step
 * sequence. Snapshotted into `deal_steps` at deal creation, so editing a flow
 * only affects future deals. `applies_to` scopes it to a talent category.
 */
class DealFlow extends Model
{
    /** @use HasFactory<DealFlowFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name', 'slug', 'description', 'applies_to', 'is_active', 'is_default',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    /**
     * The ordered steps of this flow.
     *
     * @return HasMany<DealFlowStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(DealFlowStep::class)->orderBy('position');
    }

    /**
     * @param  Builder<DealFlow>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Resolve the flow to seed a deal from: the default active flow that applies
     * to the given category (or to all). Falls back to any default active flow.
     *
     * @param  Builder<DealFlow>  $query
     */
    public function scopeApplicableTo(Builder $query, ?string $category): void
    {
        $query->where('is_active', true)
            ->when($category !== null, fn (Builder $q) => $q->whereIn('applies_to', [$category, 'all'])->orWhereNull('applies_to'))
            ->orderByDesc('is_default');
    }
}
