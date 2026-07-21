<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `talent_types` (the six skills lookup) + `talent_talent_type` pivot
 * (schema-master §1). `default_blocks` drives which blocks a new talent of that
 * type gets seeded; the pivot makes talent ↔ skill many-to-many with a
 * primary flag and ordering. name/description are translatable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talent_types', function (Blueprint $table) {
            $table->id();
            $table->json('name'); // translatable
            $table->string('slug')->unique();
            $table->enum('category', ['model', 'crew', 'creative']);
            $table->json('default_blocks'); // ordered list of block_type keys
            $table->string('icon')->nullable();
            $table->json('description')->nullable(); // translatable
            $table->timestamps();
        });

        Schema::create('talent_talent_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            $table->foreignId('talent_type_id')->constrained('talent_types')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['talent_id', 'talent_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talent_talent_type');
        Schema::dropIfExists('talent_types');
    }
};
