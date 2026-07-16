<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pricing rate (ADR-N) — the single indicative rate shown on the public profile,
 * replacing the removed rate card (ADR-K). Three all-or-nothing columns on
 * `talents`: a unit (project/day/hour), an amount, and an ISO currency. NOT
 * translatable. Nullable so the rate is optional.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talents', function (Blueprint $table) {
            $table->enum('rate_unit', ['project', 'day', 'hour'])->nullable()->after('booking_value');
            $table->decimal('rate_amount', 10, 2)->nullable()->after('rate_unit');
            $table->char('rate_currency', 3)->nullable()->after('rate_amount');
        });
    }

    public function down(): void
    {
        Schema::table('talents', function (Blueprint $table) {
            $table->dropColumn(['rate_unit', 'rate_amount', 'rate_currency']);
        });
    }
};
