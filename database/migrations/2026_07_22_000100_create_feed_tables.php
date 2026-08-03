<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The ten feed tables (schema-master §7, feed-spec, ADR-F1…F8) plus the
 * denormalized counter columns on talents and brands (ADR-F6).
 *
 * Every actor-side reference is a polymorphic morph stored under the enforced
 * aliased morph map (talent / brand / user / post / comment / project) so
 * brands can be enabled as authors later by policy alone (ADR-F1). Composite
 * indexes are part of the contract (feed candidate query, comment listing,
 * follower lookups) — not optional.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('author_type');
            $table->unsignedBigInteger('author_id');
            $table->enum('post_type', ['note', 'media', 'project_share']);
            $table->longText('body')->nullable();
            // The shared talent-owned Project (project_share only) — live
            // reference with a frozen snapshot fallback (ADR-F2).
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->json('shared_snapshot')->nullable();
            $table->string('state');
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('shares_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['author_type', 'author_id', 'published_at']);
            $table->index(['state', 'published_at']);
        });

        Schema::create('post_skill', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->foreignId('talent_type_id')->constrained('talent_types')->cascadeOnDelete();

            $table->primary(['post_id', 'talent_type_id']);
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            // Two-level thread (ADR-F7): NULL = top-level; a reply to a reply
            // re-attaches to the same parent, so this is never more than one hop.
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->string('author_type');
            $table->unsignedBigInteger('author_id');
            $table->text('body');
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('replies_count')->default(0);
            $table->string('state');
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['post_id', 'parent_id', 'created_at']);
            $table->index(['author_type', 'author_id']);
        });

        Schema::create('likes', function (Blueprint $table) {
            $table->id();
            $table->string('likeable_type');
            $table->unsignedBigInteger('likeable_id');
            $table->string('liker_type');
            $table->unsignedBigInteger('liker_id');
            $table->timestamps();

            // The idempotency guarantee: ToggleLike relies on this unique index
            // under double submission instead of a read-then-write race.
            $table->unique(['likeable_type', 'likeable_id', 'liker_type', 'liker_id'], 'likes_unique');
            $table->index(['likeable_type', 'likeable_id']);
            $table->index(['liker_type', 'liker_id']);
        });

        Schema::create('follows', function (Blueprint $table) {
            $table->id();
            $table->string('follower_type');
            $table->unsignedBigInteger('follower_id');
            $table->string('followable_type');
            $table->unsignedBigInteger('followable_id');
            $table->timestamp('created_at');

            $table->unique(['follower_type', 'follower_id', 'followable_type', 'followable_id'], 'follows_unique');
            $table->index(['follower_type', 'follower_id']);
            $table->index(['followable_type', 'followable_id']);
        });

        Schema::create('mentions', function (Blueprint $table) {
            $table->id();
            $table->string('mentionable_type');
            $table->unsignedBigInteger('mentionable_id');
            $table->string('mentioned_type');
            $table->unsignedBigInteger('mentioned_id');
            // Multibyte-correct character offsets into the RAW body (ADR-F3);
            // the renderer resolves the current Username at read time.
            $table->unsignedInteger('start_offset');
            $table->unsignedInteger('length');
            $table->timestamps();

            $table->index(['mentionable_type', 'mentionable_id']);
            $table->index(['mentioned_type', 'mentioned_id']);
        });

        Schema::create('link_previews', function (Blueprint $table) {
            $table->id();
            // SHA-256 of the normalized URL — the dedupe key (ADR-F4).
            $table->char('url_hash', 64)->unique();
            $table->text('url');
            $table->text('canonical_url')->nullable();
            $table->string('provider');
            $table->string('provider_handle')->nullable();
            $table->string('provider_resource_id')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('image_url', 2048)->nullable();
            $table->text('embed_html')->nullable();
            $table->enum('status', ['pending', 'ok', 'failed'])->default('pending');
            $table->timestamp('fetched_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('linked_urls', function (Blueprint $table) {
            $table->id();
            $table->string('linkable_type');
            $table->unsignedBigInteger('linkable_id');
            $table->foreignId('link_preview_id')->constrained('link_previews')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['linkable_type', 'linkable_id']);
        });

        Schema::create('post_reports', function (Blueprint $table) {
            $table->id();
            // Despite the table name, `reportable` covers posts AND comments.
            $table->string('reportable_type');
            $table->unsignedBigInteger('reportable_id');
            $table->string('reporter_type');
            $table->unsignedBigInteger('reporter_id');
            $table->enum('reason', ['spam', 'harassment', 'inappropriate', 'copyright', 'misleading', 'other']);
            $table->text('note')->nullable();
            $table->enum('status', ['open', 'reviewing', 'actioned', 'dismissed'])->default('open');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            // One report per reportable per reporter (policy re-checks; this backs it).
            $table->unique(['reportable_type', 'reportable_id', 'reporter_type', 'reporter_id'], 'post_reports_unique');
            $table->index(['reportable_type', 'reportable_id']);
            $table->index('status');
        });

        Schema::create('feed_seen', function (Blueprint $table) {
            $table->id();
            $table->string('viewer_type');
            $table->unsignedBigInteger('viewer_id');
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->timestamp('seen_at');

            $table->unique(['viewer_type', 'viewer_id', 'post_id'], 'feed_seen_unique');
            $table->index(['viewer_type', 'viewer_id', 'seen_at']);
        });

        Schema::table('talents', function (Blueprint $table) {
            $table->unsignedInteger('followers_count')->default(0)->after('view_count');
            $table->unsignedInteger('following_count')->default(0)->after('followers_count');
            $table->unsignedInteger('posts_count')->default(0)->after('following_count');
        });

        Schema::table('brands', function (Blueprint $table) {
            // Brands follow talents; followers_count is deliberately omitted
            // until feed.allow_following_brands flips (schema-master §7).
            $table->unsignedInteger('following_count')->default(0)->after('view_count');
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn('following_count');
        });

        Schema::table('talents', function (Blueprint $table) {
            $table->dropColumn(['followers_count', 'following_count', 'posts_count']);
        });

        Schema::dropIfExists('feed_seen');
        Schema::dropIfExists('post_reports');
        Schema::dropIfExists('linked_urls');
        Schema::dropIfExists('link_previews');
        Schema::dropIfExists('mentions');
        Schema::dropIfExists('follows');
        Schema::dropIfExists('likes');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('post_skill');
        Schema::dropIfExists('posts');
    }
};
