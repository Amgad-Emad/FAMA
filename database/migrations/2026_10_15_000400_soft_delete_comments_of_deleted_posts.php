<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Since 2026-10-15 a soft-deleted post takes its comments with it
 * (App\Feed\PostPurger). Bring rows deleted before that in line: comments of
 * an already soft-deleted post get the post's deletion time.
 */
return new class extends Migration
{
    /** Soft-delete live comments whose post is soft-deleted. */
    public function up(): void
    {
        DB::table('comments')
            ->join('posts', 'posts.id', '=', 'comments.post_id')
            ->whereNull('comments.deleted_at')
            ->whereNotNull('posts.deleted_at')
            ->update(['comments.deleted_at' => DB::raw('posts.deleted_at'), 'comments.updated_at' => now()]);
    }

    /** Not reversed (the cascade is the intended state). */
    public function down(): void {}
};
