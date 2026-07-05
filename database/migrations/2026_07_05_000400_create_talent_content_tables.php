<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Talent content tables (schema-master §2). Each hangs off a talent.
 *
 * Media rule applied throughout: uploaded-asset `*_url` / `thumbnail_url`
 * columns are dropped (served from the media library via accessors); only
 * EXTERNAL links/embeds keep plain URL columns — portfolio embeds, showreel
 * video_url, brand-collab url, case-study url, agency url, press url.
 * Translatable copy is stored as JSON per locale (see docs/conventions.md).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Gallery — one row per image/video/embed.
        Schema::create('portfolio_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            $table->foreignId('block_id')->nullable()->constrained('profile_blocks')->nullOnDelete();
            $table->enum('media_type', ['image', 'video', 'embed'])->default('image');
            $table->string('embed_url')->nullable(); // external only (media_type = embed); uploads use media
            $table->json('caption')->nullable();      // translatable
            $table->json('credits')->nullable();
            $table->json('tags')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        // Past brand work (logo uploaded → media; url is the external project link).
        Schema::create('brand_collabs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            $table->string('brand_name');
            $table->json('project_title')->nullable(); // translatable
            $table->smallInteger('year')->nullable();
            $table->string('url')->nullable();          // external
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        // Client/peer testimonials (moderated). Body stays in its original language.
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            $table->string('reviewer_name');
            $table->string('reviewer_role')->nullable();
            $table->string('reviewer_company')->nullable();
            $table->unsignedTinyInteger('rating'); // 1–5
            $table->text('body');
            $table->string('project_type')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        // Rate card.
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            $table->json('name');                    // translatable
            $table->json('description')->nullable(); // translatable
            $table->decimal('price', 10, 2)->nullable();
            $table->char('currency', 3)->default('EGP');
            $table->enum('price_unit', ['hour', 'day', 'project', 'fixed'])->default('project');
            $table->integer('duration_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        // Model comp card — 1:1 with the talent.
        Schema::create('comp_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->unique()->constrained('talents')->cascadeOnDelete();
            $table->unsignedSmallInteger('height_cm')->nullable();
            $table->unsignedSmallInteger('bust_cm')->nullable();
            $table->unsignedSmallInteger('waist_cm')->nullable();
            $table->unsignedSmallInteger('hips_cm')->nullable();
            $table->string('shoe_size')->nullable();
            $table->string('dress_size')->nullable();
            $table->string('hair_color')->nullable();
            $table->string('eye_color')->nullable();
            $table->string('skin_tone')->nullable();
            $table->json('measurements')->nullable();
            $table->timestamps();
        });

        // Model looks.
        Schema::create('look_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            $table->json('name'); // translatable
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        // Model polaroids/digitals (uploaded → media).
        Schema::create('digitals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            $table->enum('shot_type', ['front', 'side', 'back', 'full', 'headshot', 'smile'])->default('front');
            $table->date('captured_at')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        // Crew/creative video reels (video_url external; thumbnail uploaded → media).
        Schema::create('showreels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            $table->json('title')->nullable(); // translatable
            $table->string('video_url');        // external
            $table->enum('platform', ['youtube', 'vimeo', 'self_hosted'])->default('youtube');
            $table->integer('duration_seconds')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        // Crew kit — queryable ("who owns a RED camera"), so brand/model stay plain columns.
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            $table->enum('category', ['camera', 'lens', 'lighting', 'audio', 'grip', 'drone', 'accessory'])->default('camera');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('name');
            $table->json('notes')->nullable(); // translatable
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->index(['talent_id', 'category']);
        });

        // Long-form creative case studies (cover uploaded → media; url external).
        Schema::create('case_studies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            $table->json('title');                   // translatable
            $table->string('client_name')->nullable();
            $table->json('role')->nullable();        // translatable
            $table->json('summary')->nullable();     // translatable
            $table->json('body')->nullable();        // translatable
            $table->json('results')->nullable();
            $table->smallInteger('year')->nullable();
            $table->string('url')->nullable();        // external
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        // Creative tools — queryable ("designers who know Figma"); icon uploaded → media.
        Schema::create('software_stack', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            $table->string('software_name');
            $table->enum('proficiency', ['beginner', 'intermediate', 'advanced', 'expert'])->default('intermediate');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->index(['talent_id', 'software_name']);
        });

        // Agency representation (logo uploaded → media; agency_url external).
        Schema::create('agency_affiliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            $table->string('agency_name');
            $table->string('agency_url')->nullable(); // external
            $table->enum('representation_type', ['exclusive', 'non_exclusive', 'mother_agency', 'freelance'])->default('freelance');
            $table->string('region')->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamps();
        });

        // Press mentions (thumbnail uploaded → media; url external; titles kept as-published).
        Schema::create('press_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            $table->string('publication');
            $table->string('title');
            $table->string('url')->nullable(); // external
            $table->date('published_date')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('press_features');
        Schema::dropIfExists('agency_affiliations');
        Schema::dropIfExists('software_stack');
        Schema::dropIfExists('case_studies');
        Schema::dropIfExists('equipment');
        Schema::dropIfExists('showreels');
        Schema::dropIfExists('digitals');
        Schema::dropIfExists('look_types');
        Schema::dropIfExists('comp_cards');
        Schema::dropIfExists('services');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('brand_collabs');
        Schema::dropIfExists('portfolio_items');
    }
};
