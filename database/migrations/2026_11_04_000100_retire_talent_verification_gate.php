<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * ADR-AC (2026-11-04): admin verification is no longer a gate for a talent to
 * go Live — saving the three Go Live steps (display name · a skill with a
 * speciality · city) IS going Live. Data-only, in plain SQL so it never drifts
 * with app code:
 *
 *  1. Every already-Live talent gets `steps_completed_at` stamped, so the new
 *     one-time "you're Live" e-mail never goes to someone Live for months.
 *  2. Draft/Created talents whose Go Live steps are already saved (the ones
 *     who were waiting for Fama's check) go Live now — silently stamped too,
 *     mirroring what SyncGoLiveState would do on their next save.
 *
 * Unpublished/Suspended rows are deliberate holds and are left alone.
 * `is_verified` is kept untouched (a non-gating "checked by Fama" badge).
 */
return new class extends Migration
{
    /** Stamp Live profiles, then publish every ready Draft/Created profile. */
    public function up(): void
    {
        DB::table('talents')
            ->where('is_published', true)
            ->whereNull('steps_completed_at')
            ->update(['steps_completed_at' => now()]);

        DB::table('talents')
            ->whereNull('deleted_at')
            ->whereIn('status', ['draft', 'created'])
            ->whereNotNull('display_name')->where('display_name', '!=', '')
            ->whereNotNull('base_city')->where('base_city', '!=', '')
            ->whereExists(fn (Builder $q) => $q->selectRaw('1')->from('speciality_talent')
                ->whereColumn('speciality_talent.talent_id', 'talents.id'))
            ->update([
                'status' => 'live',
                'is_published' => true,
                'published_at' => DB::raw('COALESCE(published_at, NOW())'),
                'steps_completed_at' => DB::raw('COALESCE(steps_completed_at, NOW())'),
            ]);
    }

    /** Data-only and not reversible (which rows were published here is not recorded). */
    public function down(): void
    {
        // Intentionally empty.
    }
};
