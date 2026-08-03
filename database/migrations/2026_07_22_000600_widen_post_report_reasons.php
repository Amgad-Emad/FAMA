<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Report reasons become config-driven (round 3, 5AF): the enum column turns
 * into a plain string validated against config('feed.report_reasons'), and
 * the legacy values map onto the richer taxonomy. Old rows are migrated;
 * down() restores the original enum (new-taxonomy rows collapse to 'other').
 */
return new class extends Migration
{
    private array $forward = [
        'inappropriate' => 'nudity',
        'copyright' => 'ip',
        'misleading' => 'spam',
    ];

    public function up(): void
    {
        Schema::table('post_reports', function ($table) {
            $table->string('reason', 40)->change();
        });

        foreach ($this->forward as $old => $new) {
            DB::table('post_reports')->where('reason', $old)->update(['reason' => $new]);
        }
    }

    public function down(): void
    {
        DB::table('post_reports')->whereNotIn('reason', ['spam', 'harassment', 'inappropriate', 'copyright', 'misleading', 'other'])
            ->update(['reason' => 'other']);

        Schema::table('post_reports', function ($table) {
            $table->enum('reason', ['spam', 'harassment', 'inappropriate', 'copyright', 'misleading', 'other'])->change();
        });
    }
};
