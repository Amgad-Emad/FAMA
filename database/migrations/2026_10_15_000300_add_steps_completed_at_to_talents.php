<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When the talent first finished the six profile steps (2026-10-15). Set once,
 * atomically, by ProfileCompletionService::notifyIfComplete() — it is also the
 * guard that the "you're done, we're checking" email goes out exactly once.
 */
return new class extends Migration
{
    /** Add the stamp. */
    public function up(): void
    {
        Schema::table('talents', function (Blueprint $table) {
            $table->timestamp('steps_completed_at')->nullable()->after('featured_project_id');
        });
    }

    /** Drop the stamp. */
    public function down(): void
    {
        Schema::table('talents', function (Blueprint $table) {
            $table->dropColumn('steps_completed_at');
        });
    }
};
