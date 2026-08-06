<?php

namespace App\Models;

use Database\Factories\ContractTemplateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Contract template (schema-master §3, ADR-V) — the admin-authored "contract
 * schema": the standard terms parameters (deposit %, cancellation forfeit %,
 * revision rounds) plus an ordered, fully custom `clauses` list. Linked to a
 * contract_flow; the resolved terms are frozen onto `contracts.terms_snapshot`
 * at contract creation, so editing a template only affects future contracts.
 */
class ContractTemplate extends Model
{
    /** @use HasFactory<ContractTemplateFactory> */
    use HasFactory, LogsActivity;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name', 'slug', 'description', 'applies_to', 'is_active', 'is_default',
        'deposit_pct', 'deposit_holder', 'cancellation_forfeit_pct', 'revision_rounds',
        'preview_currency', 'intro', 'clauses',
    ];

    /**
     * Auto-slug from the name on create when one is not supplied (guarded like
     * Brand/Talent) so admin authoring never has to hand-write a slug.
     */
    protected static function booted(): void
    {
        static::creating(function (ContractTemplate $template): void {
            if (blank($template->slug)) {
                $template->slug = Str::slug($template->name).'-'.Str::lower(Str::random(6));
            }
        });
    }

    /**
     * A template edit silently reshapes every FUTURE contract's document, so it is
     * traced through the shared `contract_flow` audit log (dirty-only).
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('contract_flow')
            ->logOnly(['name', 'applies_to', 'is_active', 'is_default', 'deposit_pct', 'deposit_holder', 'cancellation_forfeit_pct', 'revision_rounds', 'intro', 'clauses'])
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
            'deposit_pct' => 'integer',
            'cancellation_forfeit_pct' => 'integer',
            'revision_rounds' => 'integer',
            'clauses' => 'array',
        ];
    }

    /**
     * Flows that render their contract document from this template.
     *
     * @return HasMany<ContractFlow, $this>
     */
    public function flows(): HasMany
    {
        return $this->hasMany(ContractFlow::class);
    }

    /**
     * @param  Builder<ContractTemplate>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * The frozen terms payload copied onto a contract at creation (ADR-V). Only the
     * template's own terms travel — the contract's live values (scope/fee/dates)
     * are read at render time, so a document "builds itself" as steps complete.
     *
     * @return array<string, mixed>
     */
    public function toTermsSnapshot(): array
    {
        return [
            'intro' => $this->intro,
            'deposit_pct' => $this->deposit_pct,
            'deposit_holder' => $this->deposit_holder,
            'cancellation_forfeit_pct' => $this->cancellation_forfeit_pct,
            'revision_rounds' => $this->revision_rounds,
            'clauses' => $this->clauses ?? [],
        ];
    }

    /**
     * The seeded default clause list — the six standard Fama terms shown in the
     * builder mockup. Bodies carry numeric tokens resolved at render.
     *
     * @return list<array{label: string, body: string}>
     */
    public static function defaultClauses(): array
    {
        return [
            ['label' => 'Payment', 'body' => '{deposit_pct}% down, held by {deposit_holder} · {rest_pct}% on delivery.'],
            ['label' => 'Cancellation after down payment', 'body' => 'Forfeits {forfeit_pct}% to the talent.'],
            ['label' => 'Revisions', 'body' => '{revisions} round(s) included by default.'],
            ['label' => 'Usage', 'body' => 'Brand only, for the purpose stated in the scope above.'],
            ['label' => 'Late delivery', 'body' => 'Talent proposes a revised date; repeat delays affect standing.'],
            ['label' => 'Disputes', 'body' => 'Routed through Fama support first.'],
        ];
    }
}
