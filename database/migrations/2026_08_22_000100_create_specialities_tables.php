<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Specialities — the SECOND level of the controlled vocabulary (client spec
 * "Skills & specialities", ADR-Y).
 *
 * A skill (`talent_types`) nests into four-to-seven specialities and stops
 * there: two levels is the whole tree. The list is CONTROLLED — talents pick
 * from it and can never type into it, because a free-text level would break
 * every filter that depends on the same name appearing on both sides
 * (a portfolio and a project).
 *
 * `is_regional` marks the entries added for this market specifically (modest
 * fashion, Arabic lettering) — the spec renders them in the accent colour and
 * the admin catalog needs to know which ones they are.
 *
 * Three tables:
 *  - `specialities`      the catalog itself, owned by one skill
 *  - `speciality_talent` what a talent claims (always under a skill they hold)
 *  - `project_speciality` what one project drew on (the searchable link between
 *                         a piece of work and the people who do that work)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specialities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_type_id')->constrained('talent_types')->cascadeOnDelete();
            $table->json('name');            // translatable {en, ar}
            $table->string('slug');          // unique WITHIN a skill, not globally:
            $table->json('description')->nullable(); // "fashion" lives under three skills
            $table->boolean('is_regional')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['talent_type_id', 'slug']);
            $table->index(['talent_type_id', 'position']);
        });

        Schema::create('speciality_talent', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            $table->foreignId('speciality_id')->constrained('specialities')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['talent_id', 'speciality_id']);
            // Discovery filters on the speciality and hydrates the talents.
            $table->index(['speciality_id', 'talent_id']);
        });

        Schema::create('project_speciality', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('speciality_id')->constrained('specialities')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['project_id', 'speciality_id']);
            $table->index(['speciality_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_speciality');
        Schema::dropIfExists('speciality_talent');
        Schema::dropIfExists('specialities');
    }
};
