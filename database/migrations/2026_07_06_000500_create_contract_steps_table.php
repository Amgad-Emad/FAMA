<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contract steps (schema-master §3) — the per-contract snapshot of the flow's steps and
 * the progress tracker. Each row owns its status and `payload` (captured quote,
 * brief answers, upload references). `completed_by` is a polymorphic actor
 * (talents / brands / users). Also closes the circular FK: contracts.current_step_id
 * → contract_steps.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('flow_step_id')->nullable()->constrained('contract_flow_steps')->nullOnDelete();
            $table->string('key');
            $table->string('name');
            $table->enum('actor', ['brand', 'talent', 'both', 'admin', 'system']);
            $table->enum('step_type', ['form', 'approval', 'upload', 'payment', 'contract', 'message', 'schedule', 'info']);
            $table->unsignedInteger('position')->default(0);
            $table->enum('status', ['pending', 'active', 'awaiting_action', 'completed', 'skipped', 'rejected'])->default('pending');
            $table->boolean('is_required')->default(true);
            $table->boolean('is_skippable')->default(false);
            $table->json('settings')->nullable();  // snapshotted per-step config (ADR-4)
            $table->json('payload')->nullable();    // captured data
            $table->nullableMorphs('completed_by');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['contract_id', 'position']);
            $table->index(['contract_id', 'status']);
        });

        // Close the circular FK now that contract_steps exists.
        Schema::table('contracts', function (Blueprint $table) {
            $table->foreign('current_step_id')->references('id')->on('contract_steps')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['current_step_id']);
        });

        Schema::dropIfExists('contract_steps');
    }
};
