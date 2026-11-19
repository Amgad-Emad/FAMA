<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Projects can be liked (ADR-AJ, prototype project.html "♡ Like"). Likes are the
 * same polymorphic `likes` rows posts and comments use (likeable_type
 * 'project'); this is their denormalised counter, moved only by CounterService
 * (LikeObserver) and reconciled nightly like the others.
 */
return new class extends Migration
{
    /**
     * Add the counter.
     */
    public function up(): void
    {
        Schema::table('projects', fn (Blueprint $table) => $table->unsignedInteger('likes_count')->default(0));
    }

    /**
     * Drop the counter.
     */
    public function down(): void
    {
        Schema::table('projects', fn (Blueprint $table) => $table->dropColumn('likes_count'));
    }
};
