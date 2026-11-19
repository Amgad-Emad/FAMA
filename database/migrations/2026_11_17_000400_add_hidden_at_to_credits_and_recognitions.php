<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hide a single credit or recognition from your own profile (ADR-AI, prototype
 * credits.html). `hidden_at` is the talent's choice and nobody else's:
 *  - a hidden CREDIT leaves the collaborator's portfolio but stays on the project
 *    page (the project's record of who made it is not theirs to edit);
 *  - a hidden RECOGNITION leaves the profile it was written for.
 * Both come back any time (null again).
 */
return new class extends Migration
{
    /**
     * Add the columns.
     */
    public function up(): void
    {
        Schema::table('project_credits', fn (Blueprint $table) => $table->timestamp('hidden_at')->nullable()->after('status'));
        Schema::table('recognitions', fn (Blueprint $table) => $table->timestamp('hidden_at')->nullable());
    }

    /**
     * Drop the columns.
     */
    public function down(): void
    {
        Schema::table('project_credits', fn (Blueprint $table) => $table->dropColumn('hidden_at'));
        Schema::table('recognitions', fn (Blueprint $table) => $table->dropColumn('hidden_at'));
    }
};
