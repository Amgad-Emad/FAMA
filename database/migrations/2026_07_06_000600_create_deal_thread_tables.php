<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deal thread + enquiry (schema-master §3).
 *
 * `deal_messages` is the chat-like timeline: free-text messages and system
 * events interleaved. `status` (sent → read) is the DealMessage state-machine
 * column; `read_at` is its synced projection (same convention as the Phase 1B
 * state columns). `deal_enquiries` is the pre-auth Contact capture that converts
 * into a deal once the visitor becomes a brand.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deal_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained('deals')->cascadeOnDelete();
            $table->foreignId('deal_step_id')->nullable()->constrained('deal_steps')->nullOnDelete();
            $table->nullableMorphs('sender');                 // null for system
            $table->enum('sender_role', ['brand', 'talent', 'admin', 'system']);
            $table->enum('type', ['message', 'system_event', 'action_summary'])->default('message');
            $table->text('body')->nullable();
            $table->json('attachments')->nullable();
            $table->enum('status', ['sent', 'read'])->default('sent');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['deal_id', 'created_at']);
        });

        Schema::create('deal_enquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->string('contact_name');
            $table->string('contact_email');
            $table->string('contact_company')->nullable();
            $table->text('brief');
            $table->enum('status', ['new', 'converted', 'declined', 'expired'])->default('new');
            $table->foreignId('converted_deal_id')->nullable()->constrained('deals')->nullOnDelete();
            $table->timestamps();

            $table->index(['talent_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_enquiries');
        Schema::dropIfExists('deal_messages');
    }
};
