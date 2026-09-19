<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * COLLABORATORS — one credit per person per project (ADR-AA).
 *
 * The spec's unit is `person + skill + role → one credit`, and the reason all
 * three are needed is that SKILL says what someone can do while ROLE says what
 * they did HERE. On a shoot with six people credited, only the role tells you
 * who directed and who assisted.
 *
 * TWO KINDS OF CREDIT, and the difference decides everything else:
 *
 *  - ON-PLATFORM (`talent_id` set). Tagging writes this credit onto the tagged
 *    person's portfolio too, so it REQUIRES THEIR CONFIRMATION — otherwise
 *    anyone could attach a name to work they did not do. Lands `pending`, and
 *    stays invisible everywhere (public project page included) until accepted.
 *
 *  - OFF-PLATFORM (`talent_id` NULL, `name` set). A real credit for someone who
 *    is not on Fama. There is nobody to ask and no portfolio to write to, so it
 *    is `listed` immediately and never leaves this project. It cannot be used to
 *    claim credit on anyone's behalf, which is why the confirmation rule does
 *    not apply to it.
 *
 * `UNIQUE(project_id, talent_id)` stops the same person being credited twice on
 * one project. It is deliberately not extended to `name`: two off-platform
 * people can legitimately share a name.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();

            // The collaborator. NULL = an off-platform credit carried by `name`.
            $table->foreignId('talent_id')->nullable()->constrained('talents')->cascadeOnDelete();
            $table->string('name')->nullable();

            // What they can do (the controlled Skills catalog — ADR-Y) …
            $table->foreignId('talent_type_id')->constrained('talent_types')->cascadeOnDelete();
            // … and what they did on THIS project.
            $table->string('role');

            $table->string('status')->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['project_id', 'talent_id']);
            $table->index(['project_id', 'position']);
            // The collaborator's own views: their pending confirmations, and the
            // accepted credits that surface on their portfolio.
            $table->index(['talent_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_credits');
    }
};
