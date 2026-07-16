<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Brand satellites (schema-master §4). ADR-6 applied: query-critical arrays are
 * promoted to pivots for discovery — `brand_aesthetics.mood_tags` → brand_mood_tags,
 * `brand_creative_needs.talent_types` → brand_creative_need_talent_type,
 * `brand_creative_needs.project_types` → brand_creative_need_project_type. Free-text
 * `brand_references` and internal `budget_tier`/`brief_quality_score` stay put.
 * Uploaded images go through medialibrary (no *_url columns).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1:1 aesthetic direction — the richest discovery signal.
        Schema::create('brand_aesthetics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->unique()->constrained('brands')->cascadeOnDelete();
            $table->text('brand_references')->nullable();   // free text (kept, per ADR-6)
            $table->timestamps();
        });

        // mood_tags promoted to a pivot for "brands with an editorial mood".
        Schema::create('brand_mood_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_aesthetic_id')->constrained('brand_aesthetics')->cascadeOnDelete();
            $table->enum('tag', ['editorial', 'minimal', 'bold', 'warm', 'dark', 'playful', 'luxurious', 'raw', 'nostalgic', 'commercial']);
            $table->timestamps();

            $table->unique(['brand_aesthetic_id', 'tag']);
            $table->index('tag');
        });

        // The 2–3 uploaded brand images (media via medialibrary).
        Schema::create('brand_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['brand_id', 'position']);
        });

        // 1:1 creative needs — drives the discovery feed + brief pre-fill.
        Schema::create('brand_creative_needs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->unique()->constrained('brands')->cascadeOnDelete();
            $table->enum('project_frequency', ['occasional', 'monthly', 'weekly', 'ongoing'])->nullable();
            $table->enum('budget_tier', ['under_500', '500_2000', '2000_10000', '10000_plus'])->nullable(); // internal
            $table->timestamps();
        });

        // talent_types promoted to a pivot: "all brands needing photographers".
        Schema::create('brand_creative_need_talent_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_creative_need_id')->constrained('brand_creative_needs')->cascadeOnDelete();
            $table->foreignId('talent_type_id')->constrained('talent_types')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['brand_creative_need_id', 'talent_type_id'], 'bcn_talent_type_unique');
            $table->index('talent_type_id');
        });

        // project_types promoted to a pivot (controlled vocabulary).
        Schema::create('brand_creative_need_project_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_creative_need_id')->constrained('brand_creative_needs')->cascadeOnDelete();
            $table->enum('project_type', ['editorial', 'lookbook', 'campaign_video', 'social_content', 'brand_identity']);
            $table->timestamps();

            $table->unique(['brand_creative_need_id', 'project_type'], 'bcn_project_type_unique');
            $table->index('project_type');
        });

        // 1:1 denormalized trust counters (read cheaply on the profile).
        Schema::create('brand_credibility', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->unique()->constrained('brands')->cascadeOnDelete();
            $table->unsignedInteger('completed_projects_count')->default(0);
            $table->decimal('avg_response_time_hours', 6, 2)->nullable();
            $table->unsignedTinyInteger('response_rate_pct')->nullable();
            $table->decimal('brief_quality_score', 4, 2)->nullable();   // internal
            $table->timestamps();
        });

        // Talent → brand reviews (three sub-ratings). Mirrors talent-side reviews;
        // `status` is the (Phase 2B) state column, `is_approved` its projection.
        Schema::create('brand_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->nullOnDelete();
            $table->unsignedTinyInteger('communication_rating');
            $table->unsignedTinyInteger('fairness_rating');
            $table->unsignedTinyInteger('creative_respect_rating');
            $table->text('body')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();

            $table->index(['brand_id', 'status']);
        });

        // Settings-stage social handles (reorderable list).
        Schema::create('brand_social_handles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->enum('platform', ['instagram', 'tiktok', 'x', 'linkedin', 'youtube', 'facebook', 'behance', 'website', 'other']);
            $table->string('handle');
            $table->string('url');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['brand_id', 'position']);
        });

        // Append-only behaviour log feeding the preference engine. Analytics-store
        // candidate if volume grows (see docs/schema.md).
        Schema::create('brand_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->foreignId('talent_id')->nullable()->constrained('talents')->nullOnDelete();
            $table->enum('action_type', ['view', 'save', 'brief_sent', 'profile_open']);
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent();  // append-only (no updated_at)

            $table->index(['brand_id', 'created_at']);
            $table->index('action_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_signals');
        Schema::dropIfExists('brand_social_handles');
        Schema::dropIfExists('brand_reviews');
        Schema::dropIfExists('brand_credibility');
        Schema::dropIfExists('brand_creative_need_project_type');
        Schema::dropIfExists('brand_creative_need_talent_type');
        Schema::dropIfExists('brand_creative_needs');
        Schema::dropIfExists('brand_images');
        Schema::dropIfExists('brand_mood_tags');
        Schema::dropIfExists('brand_aesthetics');
    }
};
