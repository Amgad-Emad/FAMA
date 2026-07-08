<?php

namespace App\Models;

use App\States\DealFlow\DealFlowState;
use Database\Factories\DealFlowFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\ModelStates\HasStates;

/**
 * Deal flow (schema-master §3) — a named, reusable, admin-authored step
 * sequence. Snapshotted into `deal_steps` at deal creation, so editing a flow
 * only affects future deals. `applies_to` scopes it to a talent category.
 * Admin edits are audited via spatie/laravel-activitylog (subject + causer +
 * old/new properties) for the coming authoring layer.
 */
class DealFlow extends Model
{
    /** @use HasFactory<DealFlowFactory> */
    use HasFactory, HasStates, LogsActivity;

    /**
     * Audit flow edits (admin-governed) — subject + causer + changed attributes.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('deal_flow')
            ->logOnly(['name', 'slug', 'description', 'applies_to', 'is_active', 'is_default', 'status'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name', 'slug', 'description', 'applies_to', 'is_active', 'is_default', 'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'status' => DealFlowState::class,
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
