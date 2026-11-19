<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "About your practice" (ADR-AG): each skill's custom fields, stored ONCE per
 * profile keyed by field id (App\Support\PracticeFields), so a field two skills
 * share — Dialects for Acting and Voice — is filled in once. Digitals and
 * Software are views of existing data and are never stored here.
 */
return new class extends Migration
{
    /**
     * Add the column.
     */
    public function up(): void
    {
        Schema::table('talents', function (Blueprint $table) {
            $table->json('practice')->nullable()->after('worldview');
        });
    }

    /**
     * Drop the column.
     */
    public function down(): void
    {
        Schema::table('talents', function (Blueprint $table) {
            $table->dropColumn('practice');
        });
    }
};
