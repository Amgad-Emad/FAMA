<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contract thread + enquiry (schema-master §3).
 *
 * `contract_messages` is the chat-like timeline: free-text messages and system
 * events interleaved. `status` (sent → read) is the ContractMessage state-machine
 * column; `read_at` is its synced projection (same convention as the Phase 1B
 * state columns). `contract_enquiries` is the pre-auth Contact capture that converts
 * into a contract once the visitor becomes a brand.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('contract_step_id')->nullable()->constrained('contract_steps')->nullOnDelete();
            $table->nullableMorphs('sender');                 // null for system
            $table->enum('sender_role', ['brand', 'talent', 'admin', 'system']);
            $table->enum('type', ['message', 'system_event', 'action_summary'])->default('message');
            $table->text('body')->nullable();
            $table->boolean('is_rich')->default(false);       // body holds sanitized HTML (applications)
            $table->json('attachments')->nullable();
            $table->enum('status', ['sent', 'read'])->default('sent');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['contract_id', 'created_at']);
        });

        Schema::create('contract_enquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->string('contact_name');
            $table->string('contact_email');
            $table->string('contact_company')->nullable();
            $table->text('brief');
            $table->enum('status', ['new', 'converted', 'declined', 'expired'])->default('new');
            $table->foreignId('converted_contract_id')->nullable()->constrained('contracts')->nullOnDelete();
            $table->timestamps();

            $table->index(['talent_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_enquiries');
        Schema::dropIfExists('contract_messages');
    }
};
