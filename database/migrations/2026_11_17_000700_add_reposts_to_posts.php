<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reposts (ADR-AM, prototype posts.js "Share to your feed"): a post of type
 * `repost` points at the post it shares, with the sharer's own words (optional)
 * as its body. The original going away leaves the repost standing with a
 * "no longer available" card (nullOnDelete).
 */
return new class extends Migration
{
    /**
     * Allow the type and add the pointer.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE posts MODIFY post_type ENUM('note', 'media', 'project_share', 'repost') NOT NULL");
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('reposted_post_id')->nullable()->after('project_id')->constrained('posts')->nullOnDelete();
        });
    }

    /**
     * Remove the pointer (reposts become plain notes) and the type.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reposted_post_id');
        });
        DB::table('posts')->where('post_type', 'repost')->update(['post_type' => 'note']);
        DB::statement("ALTER TABLE posts MODIFY post_type ENUM('note', 'media', 'project_share') NOT NULL");
    }
};
