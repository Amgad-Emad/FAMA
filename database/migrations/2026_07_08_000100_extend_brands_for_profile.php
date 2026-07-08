<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extend the Phase 1E `brands` stub into the full brand identity (schema-master
 * §4). Onboarding + settings-stage fields are all nullable so a brand can sign
 * up first and fill the profile progressively; `is_complete`/`is_published`
 * (already on the stub) remain the two gates. Logo & cover are medialibrary
 * collections (ADR-5), not columns; `description` is translatable JSON.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->json('description')->nullable()->after('name');          // translatable, one-sentence
            $table->enum('industry', ['fashion', 'beauty', 'food_beverage', 'lifestyle', 'tech', 'other'])->nullable()->after('description');
            $table->enum('brand_stage', ['new', 'growing', 'established'])->nullable()->after('industry');
            $table->string('base_city')->nullable()->after('brand_stage');
            $table->string('base_country')->nullable()->after('base_city');
            $table->enum('geographic_reach', ['same_city', 'mena', 'international'])->nullable()->after('base_country');
            $table->smallInteger('founded_year')->nullable()->after('geographic_reach');   // settings-stage
            $table->enum('company_size', ['solo', 'small', 'medium', 'large', 'enterprise'])->nullable()->after('founded_year'); // settings-stage
            $table->string('website')->nullable()->after('company_size');     // settings-stage

            // Discovery-facing filters.
            $table->index('industry');
            $table->index('geographic_reach');
            $table->index('base_city');
            $table->index('is_published');
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropIndex(['industry']);
            $table->dropIndex(['geographic_reach']);
            $table->dropIndex(['base_city']);
            $table->dropIndex(['is_published']);
            $table->dropColumn([
                'description', 'industry', 'brand_stage', 'base_city', 'base_country',
                'geographic_reach', 'founded_year', 'company_size', 'website',
            ]);
        });
    }
};
