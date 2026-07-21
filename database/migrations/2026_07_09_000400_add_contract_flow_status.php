<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Contract-flow template lifecycle (Phase 3A): draft → active → archived. `is_active`
 * becomes a synced projection of the status (App\Listeners\SyncStateProjections);
 * `is_default` stays an orthogonal flag (one default per applies_to scope).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_flows', function (Blueprint $table) {
            $table->string('status')->default('draft')->after('is_active')->index();
        });

        // Existing flows are live → active; everything else stays draft.
        DB::table('contract_flows')->where('is_active', true)->update(['status' => 'active']);
    }

    public function down(): void
    {
        Schema::table('contract_flows', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
