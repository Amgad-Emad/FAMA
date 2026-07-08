<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campaigns (schema-master §5) — a brand's public-facing project that can group
 * many deals. `status` is the (Phase 2B) lifecycle column; cover + gallery media
 * go through medialibrary; `description` and media `caption` are translatable.
 * `campaign_talent_types` names the roles sought; `campaign_media` is the gallery.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
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
            $table->unsignedInteger('positions_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['brand_id', 'status']);
            $table->index('is_public');
        });

        // Roles a campaign seeks ("1 model + 1 photographer").
        Schema::create('campaign_talent_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignId('talent_type_id')->constrained('talent_types')->cascadeOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['campaign_id', 'talent_type_id']);
            $table->index('talent_type_id');
        });

        // The campaign gallery (uploads → medialibrary; embed_url external).
        Schema::create('campaign_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->enum('media_type', ['image', 'video', 'embed'])->default('image');
            $table->string('embed_url')->nullable();     // external, for media_type = embed
            $table->json('caption')->nullable();          // translatable
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['campaign_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_media');
        Schema::dropIfExists('campaign_talent_types');
        Schema::dropIfExists('campaigns');
    }
};
