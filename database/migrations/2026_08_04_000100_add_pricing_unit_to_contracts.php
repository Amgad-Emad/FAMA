<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The agreed price can be per hour / day / item (or a flat project fee). The unit
 * is captured on the quote step and rendered in the contract document beside the
 * amount, e.g. "4,500 EGP / day".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->string('pricing_unit')->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->dropColumn('pricing_unit');
        });
    }
};
