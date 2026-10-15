<?php

use App\Support\Locations;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Country became a drop list (2026-10-15): rewrite free-text spellings that
 * map to a known country ("UAE", "KSA", "uk") to its canonical name so the
 * drop list shows them selected and discovery filters match one spelling.
 * Unknown values are left untouched. Not reversible (the old spelling is lost,
 * and it carried no meaning).
 */
return new class extends Migration
{
    /** Canonicalise talents.base_country and brands.base_country. */
    public function up(): void
    {
        foreach (['talents', 'brands'] as $table) {
            $values = DB::table($table)->whereNotNull('base_country')->where('base_country', '!=', '')->distinct()->pluck('base_country');
            foreach ($values as $value) {
                $canonical = Locations::canonicalName($value);
                if ($canonical !== $value) {
                    DB::table($table)->where('base_country', $value)->update(['base_country' => $canonical]);
                }
            }
        }
    }

    /** Nothing to restore. */
    public function down(): void {}
};
