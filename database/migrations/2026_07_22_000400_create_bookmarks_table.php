<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bookmarks (revision 5X): polymorphic on BOTH sides so saving talents and
 * Projects later is a policy change, not a migration — only
 * bookmarkable_type = post is enabled this phase. Plus the denormalized
 * posts.bookmarks_count (ADR-F6 pattern, reconciled nightly).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->string('bookmarker_type');
            $table->unsignedBigInteger('bookmarker_id');
            $table->string('bookmarkable_type');
            $table->unsignedBigInteger('bookmarkable_id');
            $table->timestamp('created_at');

            $table->unique(['bookmarker_type', 'bookmarker_id', 'bookmarkable_type', 'bookmarkable_id'], 'bookmarks_unique');
            $table->index(['bookmarker_type', 'bookmarker_id', 'created_at']);
            $table->index(['bookmarkable_type', 'bookmarkable_id']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->unsignedInteger('bookmarks_count')->default(0)->after('shares_count');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('bookmarks_count');
        });

        Schema::dropIfExists('bookmarks');
    }
};
