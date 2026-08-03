<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * talents.last_posted_at — the denormalized "quiet since" signal for the
 * FOLLOWING management surface: when did this talent last publish a post.
 * Maintained by CounterService on every publish-boundary crossing (ADR-F6
 * pattern) and reconciled nightly by feed:reconcile-counters; indexed for the
 * Least-active sort. Backfilled from the posts table on migrate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talents', function (Blueprint $table): void {
            $table->timestamp('last_posted_at')->nullable()->after('posts_count')->index();
        });

        DB::update(
            "UPDATE talents SET last_posted_at = (
                SELECT MAX(posts.published_at) FROM posts
                WHERE posts.author_type = 'talent' AND posts.author_id = talents.id
                  AND posts.state = 'published' AND posts.deleted_at IS NULL
            )"
        );
    }

    public function down(): void
    {
        Schema::table('talents', function (Blueprint $table): void {
            $table->dropColumn('last_posted_at');
        });
    }
};
