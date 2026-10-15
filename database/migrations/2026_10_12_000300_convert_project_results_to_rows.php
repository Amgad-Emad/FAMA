<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Project results become an ordered list of {label, value, compare} (fama-ui
 * handoff, 2026-10-12): a "+40%" result needs its "Compared with" line, and a
 * label→value map could neither carry it nor keep two rows with one label.
 *
 * Data-only and append-only: every legacy map row is rewritten as a list
 * (order preserved, compare = '', capped at four like the editor). Rows already
 * in the list shape are left alone, so re-running is a no-op.
 */
return new class extends Migration
{
    /**
     * Rewrite every legacy label→value map as the list shape.
     */
    public function up(): void
    {
        DB::table('projects')->whereNotNull('results')->orderBy('id')->chunkById(200, function ($rows): void {
            foreach ($rows as $row) {
                $results = json_decode((string) $row->results, true);

                if (! is_array($results) || $results === [] || array_is_list($results)) {
                    continue;
                }

                $list = collect($results)
                    ->map(fn ($value, $label) => [
                        'label' => (string) $label,
                        'value' => is_scalar($value) ? (string) $value : '',
                        'compare' => '',
                    ])
                    ->filter(fn ($r) => trim($r['label']) !== '' && trim($r['value']) !== '')
                    ->take(4)
                    ->values()
                    ->all();

                DB::table('projects')->where('id', $row->id)->update([
                    'results' => $list === [] ? null : json_encode($list),
                ]);
            }
        });
    }

    /**
     * Back to the label→value map (the compare line has nowhere to go and is dropped).
     */
    public function down(): void
    {
        DB::table('projects')->whereNotNull('results')->orderBy('id')->chunkById(200, function ($rows): void {
            foreach ($rows as $row) {
                $results = json_decode((string) $row->results, true);

                if (! is_array($results) || ! array_is_list($results)) {
                    continue;
                }

                $map = collect($results)->filter(fn ($r) => is_array($r))
                    ->mapWithKeys(fn ($r) => [(string) ($r['label'] ?? '') => (string) ($r['value'] ?? '')])
                    ->all();

                DB::table('projects')->where('id', $row->id)->update(['results' => $map === [] ? null : json_encode($map)]);
            }
        });
    }
};
