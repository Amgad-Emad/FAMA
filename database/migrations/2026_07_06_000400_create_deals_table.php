<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deals (schema-master §3) — one row per brand ↔ talent engagement. `status`
 * mirrors whose turn it is (for the inbox); `current_step_id` points at the
 * active `deal_steps` row. That FK is circular (deals ↔ deal_steps), so it is
 * added in the deal_steps migration once that table exists — here it is just a
 * nullable, indexed column. `campaign_id` is intentionally deferred (ADR-F).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();          // FAMA-2026-0001
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('deal_flow_id')->constrained('deal_flows');
            $table->unsignedBigInteger('current_step_id')->nullable(); // FK added post deal_steps

            $table->enum('status', [
                'draft', 'awaiting_brand', 'awaiting_talent', 'awaiting_admin',
                'completed', 'cancelled', 'declined', 'expired',
            ])->default('draft');

            $table->string('title');
            $table->text('brief')->nullable();
            $table->decimal('agreed_amount', 10, 2)->nullable();
            $table->char('currency', 3)->default('EGP');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('initiated_by', ['brand', 'talent']);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['talent_id', 'status']);
            $table->index(['brand_id', 'status']);
            $table->index('current_step_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
