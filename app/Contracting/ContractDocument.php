<?php

namespace App\Contracting;

use App\Models\Contract;
use App\Models\ContractTemplate;

/**
 * Builds the normalized "contract document" structure rendered by
 * resources/views/contracts/partials/document.blade.php — the single source of
 * truth shared by (a) the admin template builder's live preview and (b) the
 * talent/brand contract room. It combines a template's frozen TERMS with the
 * engagement's live DATA (talent, scope, timeline, fee) and resolves the numeric
 * token placeholders in the intro + clause bodies.
 */
class ContractDocument
{
    /**
     * Build the document for a real contract: terms come from the frozen
     * `terms_snapshot` (or the flow's template as a fallback for legacy rows);
     * the parties + scope + fee + dates are read LIVE off the contract so the
     * document reflects the current state of the flow.
     *
     * @return array<string, mixed>
     */
    public static function fromContract(Contract $contract): array
    {
        $terms = $contract->terms_snapshot
            ?: $contract->flow?->contractTemplate?->toTermsSnapshot()
            ?: self::blankTerms();

        $total = $contract->agreed_amount !== null ? (float) $contract->agreed_amount : null;

        return self::assemble($terms, [
            'ref' => $contract->reference,
            'brand' => $contract->brand?->name ?? 'the brand',
            'talent' => $contract->talent?->display_name ?? 'the talent',
            'scope' => $contract->brief,
            'timeline' => self::timeline($contract->start_date?->toFormattedDateString(), $contract->end_date?->toFormattedDateString()),
            'total' => $total,
            'currency' => $contract->currency,
            'pricing_unit' => $contract->pricing_unit,
            'exception' => null,
            'confirmed' => in_array((string) $contract->status, ['completed'], true),
        ]);
    }

    /**
     * Build the document for the admin builder's live preview: terms come from the
     * (unsaved) draft template, data comes from sample inputs the admin types.
     *
     * @param  array<string, mixed>  $sample  talent, brand, scope, timeline, total, currency, exception
     * @return array<string, mixed>
     */
    public static function fromTemplate(ContractTemplate $template, array $sample = []): array
    {
        return self::assemble($template->toTermsSnapshot(), [
            'ref' => 'DRAFT',
            'brand' => $sample['brand'] ?? 'the brand',
            'talent' => $sample['talent'] ?? 'the talent',
            'scope' => $sample['scope'] ?? null,
            'timeline' => $sample['timeline'] ?? null,
            'total' => isset($sample['total']) && $sample['total'] !== '' && $sample['total'] !== null ? (float) $sample['total'] : null,
            'currency' => $sample['currency'] ?? $template->preview_currency,
            'pricing_unit' => $sample['pricing_unit'] ?? null,
            'exception' => $sample['exception'] ?? null,
            'confirmed' => false,
        ]);
    }

    /**
     * @param  array<string, mixed>  $terms  frozen template terms
     * @param  array<string, mixed>  $data   live engagement data
     * @return array<string, mixed>
     */
    private static function assemble(array $terms, array $data): array
    {
        $depositPct = (int) ($terms['deposit_pct'] ?? 0);
        $restPct = max(0, 100 - $depositPct);
        $currency = $data['currency'] ?: 'EGP';
        $total = $data['total'];
        $down = $total !== null ? round($total * $depositPct / 100) : null;
        $onDelivery = $total !== null ? $total - $down : null;

        $tokens = [
            '{brand}' => $data['brand'],
            '{talent}' => $data['talent'],
            '{scope}' => $data['scope'] ?: '—',
            '{timeline}' => $data['timeline'] ?: '—',
            '{deposit_pct}' => $depositPct,
            '{rest_pct}' => $restPct,
            '{deposit_holder}' => $terms['deposit_holder'] ?? 'Fama',
            '{forfeit_pct}' => (int) ($terms['cancellation_forfeit_pct'] ?? 0),
            '{revisions}' => (int) ($terms['revision_rounds'] ?? 0),
            '{currency}' => $currency,
            '{total}' => $total !== null ? self::money($total, $currency) : '—',
            '{unit}' => self::unitLabel($data['pricing_unit'] ?? null),
            '{down}' => $down !== null ? self::money($down, $currency) : '—',
            '{on_delivery}' => $onDelivery !== null ? self::money($onDelivery, $currency) : '—',
        ];

        $intro = $terms['intro'] ?: 'Between {brand} and {talent}';

        // The fee reads with its pricing unit, e.g. "4,500 EGP / day".
        $unit = self::unitLabel($data['pricing_unit'] ?? null);
        $feeValue = $total !== null ? self::money($total, $currency).($unit ? ' / '.$unit : '') : null;

        $rows = [
            ['label' => 'Scope', 'value' => $data['scope'] ?: null],
            ['label' => 'Timeline', 'value' => $data['timeline'] ?: null],
            ['label' => 'Total fee', 'value' => $feeValue, 'strong' => true],
            ['label' => 'Down payment (held by '.($terms['deposit_holder'] ?? 'Fama').')', 'value' => $down !== null ? self::money($down, $currency) : null],
            ['label' => 'On delivery', 'value' => $onDelivery !== null ? self::money($onDelivery, $currency) : null],
        ];

        $clauses = array_map(fn (array $c): array => [
            'label' => (string) ($c['label'] ?? ''),
            'body' => self::resolve((string) ($c['body'] ?? ''), $tokens),
        ], array_values(array_filter($terms['clauses'] ?? [], fn ($c) => filled($c['label'] ?? null) || filled($c['body'] ?? null))));

        return [
            'org' => 'FAMA',
            'ref' => $data['ref'],
            'intro' => self::resolve($intro, $tokens),
            'rows' => $rows,
            'exception' => filled($data['exception']) ? $data['exception'] : null,
            'clauses' => $clauses,
            'confirmed' => (bool) $data['confirmed'],
            'status_line' => $data['confirmed'] ? 'Confirmed' : 'Not yet confirmed',
        ];
    }

    /**
     * @param  array<string, mixed>  $tokens
     */
    private static function resolve(string $body, array $tokens): string
    {
        return strtr($body, array_map(fn ($v) => (string) $v, $tokens));
    }

    /**
     * Human label for a pricing unit ('hour' → 'hour', 'day' → 'day', 'item' →
     * 'item', 'project' → flat fee shows no unit). Unknown values pass through.
     */
    private static function unitLabel(?string $unit): string
    {
        $unit = trim((string) $unit);

        return ($unit === '' || $unit === 'project' || $unit === 'flat') ? '' : $unit;
    }

    private static function money(float $amount, string $currency): string
    {
        return number_format($amount).' '.$currency;
    }

    private static function timeline(?string $start, ?string $end): ?string
    {
        if ($start && $end) {
            return $start.' → '.$end;
        }

        return $start ?: $end;
    }

    /**
     * @return array<string, mixed>
     */
    private static function blankTerms(): array
    {
        return [
            'intro' => null,
            'deposit_pct' => 50,
            'deposit_holder' => 'Fama',
            'cancellation_forfeit_pct' => 50,
            'revision_rounds' => 1,
            'clauses' => ContractTemplate::defaultClauses(),
        ];
    }
}
