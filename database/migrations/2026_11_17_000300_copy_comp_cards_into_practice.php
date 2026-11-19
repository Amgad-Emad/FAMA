<?php

use App\Support\PracticeFields;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The prototype's Modelling practice fields (height, measurements, sizes —
 * ADR-AG) replace the old Comp Card, which had no editor in the app. Copy each
 * comp card's numbers into talents.practice where that field is still empty,
 * so nothing a talent had is lost; the comp card block then stops rendering
 * for a profile whose practice holds these fields (one home on the page).
 * Hair/eye colour have no practice field and are left on the comp card row.
 */
return new class extends Migration
{
    /**
     * Copy comp card numbers into empty practice fields.
     */
    public function up(): void
    {
        DB::table('comp_cards')->orderBy('id')->each(function ($card): void {
            $talent = DB::table('talents')->where('id', $card->talent_id)->first(['id', 'practice']);
            if (! $talent) {
                return;
            }
            $practice = json_decode((string) $talent->practice, true) ?: [];
            $from = [
                'height' => $card->height_cm,
                'measure' => array_filter(['chest' => $card->bust_cm, 'waist' => $card->waist_cm, 'hips' => $card->hips_cm]),
                'sizes' => array_filter(['r' => 'EU', 'c' => (string) $card->dress_size, 's' => (string) $card->shoe_size], fn ($v) => $v !== ''),
            ];
            if (count($from['sizes']) === 1) {
                unset($from['sizes']); // only the default scale — nothing to copy
            }
            foreach (PracticeFields::clean($from) as $id => $value) {
                if (! PracticeFields::filled($id, $practice[$id] ?? null)) {
                    $practice[$id] = $value;
                }
            }
            DB::table('talents')->where('id', $talent->id)->update(['practice' => $practice ? json_encode($practice, JSON_UNESCAPED_UNICODE) : null]);
        });
    }

    /**
     * The copied values are indistinguishable from typed ones — nothing to undo.
     */
    public function down(): void {}
};
