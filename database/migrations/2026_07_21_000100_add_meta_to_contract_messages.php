<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * System-event messages carry an optional structured `meta` ({key, params}) so
 * their body can be localized at render for the VIEWER's locale, instead of
 * being frozen in the language of whoever performed the action. The plain
 * `body` stays as the fallback (older rows, custom flows).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_messages', function (Blueprint $table) {
            $table->json('meta')->nullable()->after('attachments');
        });
    }

    public function down(): void
    {
        Schema::table('contract_messages', fn (Blueprint $table) => $table->dropColumn('meta'));
    }
};
