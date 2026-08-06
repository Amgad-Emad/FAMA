<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contract templates — admin-authored "contract schema": the standard terms
 * (payment split, cancellation forfeit, revision rounds) + an ordered, fully
 * custom clause list that renders into a contract document. A template is linked
 * to a contract_flow; at contract creation the resolved terms are frozen onto
 * `contracts.terms_snapshot`, so later template edits never change a live contract
 * (same immutability rule as flow-step snapshots, ADR-4/ADR-V).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            // Scope to a talent category (null = all), mirroring contract_flows.
            $table->enum('applies_to', ['all', 'model', 'crew', 'creative'])->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);

            // Structured terms parameters (substituted into clause token placeholders).
            $table->unsignedTinyInteger('deposit_pct')->default(50);
            $table->string('deposit_holder')->default('Fama');
            $table->unsignedTinyInteger('cancellation_forfeit_pct')->default(50);
            $table->unsignedTinyInteger('revision_rounds')->default(1);
            $table->char('preview_currency', 3)->default('EGP');

            // The document intro line (supports {brand}/{talent} tokens) + the ordered
            // clause list [{label, body}] where body supports numeric tokens.
            $table->text('intro')->nullable();
            $table->json('clauses')->nullable();

            $table->timestamps();
            $table->index(['is_active', 'applies_to']);
        });

        Schema::table('contract_flows', function (Blueprint $table): void {
            $table->foreignId('contract_template_id')->nullable()->after('applies_to')
                ->constrained('contract_templates')->nullOnDelete();
        });

        Schema::table('contracts', function (Blueprint $table): void {
            // Frozen copy of the flow's template terms at creation (ADR-V).
            $table->json('terms_snapshot')->nullable()->after('brief');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->dropColumn('terms_snapshot');
        });
        Schema::table('contract_flows', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('contract_template_id');
        });
        Schema::dropIfExists('contract_templates');
    }
};
