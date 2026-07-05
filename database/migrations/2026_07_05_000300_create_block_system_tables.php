<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The malleable block system (schema-master §1):
 *  - block_types           the admin-governed catalog of blocks.
 *  - block_type_category   which categories a `by_category` block applies to.
 *  - block_type_talent_type  which types a `by_type` block applies to (the
 *                          finer-grained gate the spec pairs with by_category).
 *  - profile_blocks        the per-talent layout/arrangement layer (position,
 *                          visibility, layout, inline content).
 *
 * block_type name/description and profile_block title are translatable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('block_types', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // hero, gallery, comp_card, showreel…
            $table->json('name');             // translatable
            $table->json('description')->nullable(); // translatable
            $table->string('icon')->nullable();
            $table->enum('availability', ['universal', 'by_category', 'by_type'])->default('universal');
            $table->enum('content_source', ['inline', 'table'])->default('inline');
            $table->enum('default_layout', ['grid', 'carousel', 'list', 'masonry'])->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_repeatable')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->json('settings_schema')->nullable();
            $table->timestamps();
        });

        Schema::create('block_type_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_type_id')->constrained('block_types')->cascadeOnDelete();
            $table->enum('category', ['model', 'crew', 'creative']);

            $table->unique(['block_type_id', 'category']);
        });

        Schema::create('block_type_talent_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_type_id')->constrained('block_types')->cascadeOnDelete();
            $table->foreignId('talent_type_id')->constrained('talent_types')->cascadeOnDelete();

            $table->unique(['block_type_id', 'talent_type_id']);
        });

        Schema::create('profile_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            // Restrict on delete: deactivated block types are grandfathered, not deleted.
            $table->foreignId('block_type_id')->constrained('block_types');
            $table->json('title')->nullable(); // translatable
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->enum('layout', ['grid', 'carousel', 'list', 'masonry'])->nullable();
            $table->json('settings')->nullable();
            $table->json('content')->nullable();
            $table->timestamps();

            $table->index(['talent_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_blocks');
        Schema::dropIfExists('block_type_talent_type');
        Schema::dropIfExists('block_type_category');
        Schema::dropIfExists('block_types');
    }
};
