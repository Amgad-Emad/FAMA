<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rate-card removal ripple (ADR-K): the `services` table is gone, so drop the
 * `service_id` FK + column from `contracts` and `contract_enquiries`. The contract amount is
 * captured by the flow's form/quote step (FormStepHandler amount_field), not a
 * service. Runs before the `services` table itself is dropped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->dropForeign(['service_id']);
            $table->dropColumn('service_id');
        });

        Schema::table('contract_enquiries', function (Blueprint $table): void {
            $table->dropForeign(['service_id']);
            $table->dropColumn('service_id');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->foreignId('service_id')->nullable()->after('talent_id');
        });

        Schema::table('contract_enquiries', function (Blueprint $table): void {
            $table->foreignId('service_id')->nullable()->after('talent_id');
        });
    }
};
