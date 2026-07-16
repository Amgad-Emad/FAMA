<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Projects (schema-master §5) — a brand's public-facing project that can group
 * many contracts. `status` is the (Phase 2B) lifecycle column; cover + gallery media
 * go through medialibrary; `description` and media `caption` are translatable.
 * `brand_project_talent_types` names the roles sought; `brand_project_media` is the gallery.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('type', ['campaign', 'shoot'])->default('campaign');
            $table->json('description')->nullable();     // translatable
            $table->enum('status', ['draft', 'open', 'in_progress', 'completed', 'cancelled'])->default('draft');
            $table->decimal('budget_min', 10, 2)->nullable();
            $table->decimal('budget_max', 10, 2)->nullable();
            $table->char('currency', 3)->default('EGP');
            $table->string('location_city')->nullable();
            $table->string('location_country')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_public')->default(false);
            // A project seeks ONE role for ONE talent (single discipline).
            $table->foreignId('talent_type_id')->nullable()->constrained('talent_types')->nullOnDelete();
            // Budget visibility — private by default; only the owning brand sees a private budget.
            $table->boolean('budget_is_public')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['brand_id', 'status']);
            $table->index('is_public');
        });

        // The project gallery (uploads → medialibrary; embed_url external).
        Schema::create('brand_project_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_project_id')->constrained('brand_projects')->cascadeOnDelete();
            $table->enum('media_type', ['image', 'video', 'embed'])->default('image');
            $table->string('embed_url')->nullable();     // external, for media_type = embed
            $table->json('caption')->nullable();          // translatable
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['brand_project_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_project_media');
        Schema::dropIfExists('brand_projects');
    }
};
