<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contract-flow templates (schema-master §3) — the reusable, admin-authored step
 * sequences. `contract_flows` is the named flow; `contract_flow_steps` are its ordered
 * steps. These are snapshotted into `contract_steps` at contract creation, so editing a
 * flow only affects future contracts (ADR-4).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_flows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->enum('applies_to', ['all', 'model', 'crew', 'creative'])->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['is_active', 'applies_to']);
        });

        Schema::create('contract_flow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_flow_id')->constrained('contract_flows')->cascadeOnDelete();
            $table->string('key');                 // machine name: brief, quote, agreement…
            $table->string('name');
            $table->text('instructions')->nullable();
            $table->enum('actor', ['brand', 'talent', 'both', 'admin', 'system']);
            $table->enum('step_type', ['form', 'approval', 'upload', 'payment', 'contract', 'message', 'schedule', 'info']);
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_required')->default(true);
            $table->boolean('is_skippable')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['contract_flow_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_flow_steps');
        Schema::dropIfExists('contract_flows');
    }
};
