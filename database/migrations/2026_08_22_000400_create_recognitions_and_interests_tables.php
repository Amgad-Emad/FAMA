<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The portfolio spec's three remaining sections (ADR-Z).
 *
 * `talent_social_handles` — ABOUT. Instagram and anywhere else the work lives.
 * A reorderable list, mirroring `brand_social_handles` so both sides of the
 * platform describe a social presence the same way.
 *
 * `recognitions` — RECOGNITIONS. Vouches from peers and collaborators. The
 * distinction from `reviews` is WHO WRITES THEM: a review is BRAND → TALENT and
 * follows a completed booking; a recognition is TALENT → TALENT and needs no
 * booking, which is exactly why it works from day one while reviews cannot exist
 * at launch. Both are admin-moderated through the same queue. The author is
 * always a Fama talent (`author_talent_id`), so a recognition carries a real,
 * clickable person rather than a name someone typed.
 *
 * `talent_interests` — WILLING TO EXPLORE. The one part of the page that asks
 * for something instead of proving something: a direction they want to move
 * toward, tied to a skill and a kind of work. STRUCTURED on purpose — as free
 * text it reads as a mood and nobody acts on it, and brands could not filter it.
 * The skill here is deliberately NOT required to be one the talent already
 * holds: wanting to move toward a discipline you do not yet list is the entire
 * point of the section.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talent_social_handles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            $table->enum('platform', ['instagram', 'tiktok', 'x', 'linkedin', 'youtube', 'behance', 'vimeo', 'website', 'other']);
            $table->string('handle');
            $table->string('url');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['talent_id', 'position']);
            // One entry per platform per talent — two Instagram rows is a mistake.
            $table->unique(['talent_id', 'platform']);
        });

        Schema::create('recognitions', function (Blueprint $table) {
            $table->id();
            // The person being vouched for.
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            // The peer doing the vouching — always a talent on the platform.
            $table->foreignId('author_talent_id')->constrained('talents')->cascadeOnDelete();
            // How they know each other: "shot three campaigns together", "assisted
            // me for two years". This is what makes a recognition readable as
            // something other than praise.
            $table->string('relationship');
            // NOT translatable, for the same reason a review is not: a vouch is
            // kept in the language it was written.
            $table->text('body');
            $table->string('status')->default('pending');
            $table->boolean('is_approved')->default(false);
            $table->timestamp('recognized_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // A peer vouches for someone ONCE; writing again edits that vouch.
            $table->unique(['talent_id', 'author_talent_id']);
            // The public profile reads the approved ones, newest first.
            $table->index(['talent_id', 'is_approved']);
            $table->index('status');
        });

        Schema::create('talent_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            // The direction. NOT restricted to the talent's own skills (see above).
            $table->foreignId('talent_type_id')->constrained('talent_types')->cascadeOnDelete();
            // The kind of work, when they can name one. NULL = the whole skill.
            $table->foreignId('speciality_id')->nullable()->constrained('specialities')->nullOnDelete();
            // One optional line of colour. The structure above is what brands
            // filter on; this is never the thing being matched.
            $table->string('note')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['talent_id', 'position']);
            // Brands filter "who is open to X" — both halves are searchable.
            $table->index('talent_type_id');
            $table->index('speciality_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talent_interests');
        Schema::dropIfExists('recognitions');
        Schema::dropIfExists('talent_social_handles');
    }
};
