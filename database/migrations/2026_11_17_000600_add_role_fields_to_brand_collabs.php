<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Experience becomes LinkedIn-style roles (ADR-AK, prototype profile.js
 * FAMA_EXP): a role is title + company + employment type + location (+ type) +
 * start/end month-year or "currently" + highlights + the skills it used.
 * brand_collabs stays THE table (one home): `brand_name` is the company, the old
 * project/year/url/logo keep rendering for rows written before roles existed,
 * and the old `year` seeds `start_year`.
 */
return new class extends Migration
{
    /**
     * Add the role columns and backfill the start year.
     */
    public function up(): void
    {
        Schema::table('brand_collabs', function (Blueprint $table) {
            $table->string('role_title', 80)->nullable()->after('brand_name');
            $table->string('employment_type', 20)->nullable()->after('role_title');
            $table->string('location', 60)->nullable()->after('employment_type');
            $table->string('location_type', 20)->nullable()->after('location');
            $table->boolean('is_current')->default(false)->after('location_type');
            $table->unsignedTinyInteger('start_month')->nullable()->after('is_current');
            $table->unsignedSmallInteger('start_year')->nullable()->after('start_month');
            $table->unsignedTinyInteger('end_month')->nullable()->after('start_year');
            $table->unsignedSmallInteger('end_year')->nullable()->after('end_month');
            $table->text('highlights')->nullable()->after('end_year');
            $table->json('skill_ids')->nullable()->after('highlights');
        });

        DB::table('brand_collabs')->whereNull('start_year')->whereNotNull('year')->update(['start_year' => DB::raw('`year`')]);
    }

    /**
     * Drop the role columns.
     */
    public function down(): void
    {
        Schema::table('brand_collabs', function (Blueprint $table) {
            $table->dropColumn(['role_title', 'employment_type', 'location', 'location_type', 'is_current', 'start_month', 'start_year', 'end_month', 'end_year', 'highlights', 'skill_ids']);
        });
    }
};
