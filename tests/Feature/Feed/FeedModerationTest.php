<?php

use App\Models\Comment;
use App\Models\FeedSeen;
use App\Models\LinkPreview;
use App\Models\Post;
use App\Models\PostReport;
use App\Models\Talent;
use App\Models\User;
use App\Notifications\Feed\ContentRemoved;
use App\Services\FeedModerationService;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

function moderator(): User
{
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('moderator', 'admin'));
    $admin->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('moderate-content', 'admin'));

    return $admin;
}

beforeEach(function () {
    config(['queue.default' => 'sync']);
});

// ----- Service actions (state machines + audit + notice) ---------------------

it('hides, restores and removes a post through the state machine with an audit trail', function () {
    $admin = moderator();
    $talent = Talent::factory()->create();
    $post = Post::factory()->byAuthor($talent)->create();
    $service = app(FeedModerationService::class);

    $service->hidePost($post, $admin);
    expect($post->refresh()->state->getValue())->toBe('hidden');

    $service->restorePost($post, $admin);
    expect($post->refresh()->state->getValue())->toBe('published');

    $service->removePost($post, $admin, 'stolen imagery');
    expect($post->refresh()->state->getValue())->toBe('removed');

    // Audit trail carries the acting admin + the reason.
    $log = Activity::query()->where('log_name', 'feed_moderation')
        ->where('description', 'post.removed')->first();

    expect($log)->not->toBeNull()
        ->and($log->causer_id)->toBe($admin->id)
        ->and($log->properties['reason'])->toBe('stolen imagery');

    // The author was told why.
    expect($talent->notifications()->where('type', ContentRemoved::class)->count())->toBe(1);
});

it('resolves reports as actioned or dismissed with the resolver stamped', function () {
    $admin = moderator();
    $report = PostReport::query()->create([
        'reportable_type' => 'post',
        'reportable_id' => Post::factory()->create()->id,
        'reporter_type' => 'talent',
        'reporter_id' => Talent::factory()->create()->id,
        'reason' => 'spam',
        'status' => 'open',
    ]);

    app(FeedModerationService::class)->resolveReport($report, $admin, 'dismissed');

    expect($report->refresh()->status)->toBe('dismissed')
        ->and($report->resolved_by)->toBe($admin->id)
        ->and($report->resolved_at)->not->toBeNull();
});

// ----- Auto-hide threshold ---------------------------------------------------

it('auto-hides a post at the open-report threshold and flags it in the queue', function () {
    config(['feed.auto_hide_reports' => 3]);
    $post = Post::factory()->create();

    Talent::factory()->count(3)->create()->each(function ($reporter, $i) use ($post) {
        $this->actingAs($reporter, 'talent')
            ->postJson("/posts/{$post->id}/report", ['reason' => 'spam'])
            ->assertCreated();
    });

    expect($post->refresh()->state->getValue())->toBe('hidden')
        ->and(app(FeedModerationService::class)->wasAutoHidden($post))->toBeTrue();

    // A hidden post vanishes from public view (404 — existence not leaked).
    $this->getJson("/posts/{$post->id}")->assertNotFound();
});

// ----- Admin endpoints -------------------------------------------------------

it('serves the console to moderators', function () {
    $admin = moderator();
    Post::factory()->create();

    $this->actingAs($admin, 'admin')->get('/admin/moderation/feed')->assertOk();
    $this->actingAs($admin, 'admin')->getJson('/admin/moderation/feed/posts')->assertOk();
    $this->actingAs($admin, 'admin')->getJson('/admin/moderation/feed/reports')->assertOk();
});

it('bounces non-admins and permission-less admins off the console', function () {
    // Separate tests per identity — a session can hold several guards at once.
    $talent = Talent::factory()->create();
    $this->actingAs($talent, 'talent')->get('/admin/moderation/feed')->assertRedirect();
});

it('forbids an admin without moderate-content', function () {
    $plain = User::factory()->create();
    $this->actingAs($plain, 'admin')->get('/admin/moderation/feed')->assertForbidden();
});

it('moderates a comment end-to-end through the admin endpoint', function () {
    $admin = moderator();
    $comment = Comment::factory()->create();

    $this->actingAs($admin, 'admin')
        ->patchJson("/admin/moderation/feed/comments/{$comment->id}/remove", ['reason' => 'harassment'])
        ->assertOk()
        ->assertJsonPath('data.state', 'removed');
});

// ----- feed:prune ------------------------------------------------------------

it('prunes stale feed_seen rows and dead link previews', function () {
    $post = Post::factory()->create();
    $talent = Talent::factory()->create();

    FeedSeen::query()->create(['viewer_type' => 'talent', 'viewer_id' => $talent->id, 'post_id' => $post->id, 'seen_at' => now()->subDays(90)]);
    $fresh = FeedSeen::query()->create(['viewer_type' => 'brand', 'viewer_id' => 1, 'post_id' => $post->id, 'seen_at' => now()->subDays(5)]);

    $stale = fn (LinkPreview $preview) => $preview
        ->forceFill(['created_at' => now()->subDays(30), 'updated_at' => now()->subDays(30)])
        ->saveQuietly();

    $dead = LinkPreview::query()->create([
        'url_hash' => hash('sha256', 'https://dead.example'), 'url' => 'https://dead.example',
        'provider' => 'generic', 'status' => 'failed',
    ]);
    $stale($dead);
    $referenced = LinkPreview::query()->create([
        'url_hash' => hash('sha256', 'https://alive.example'), 'url' => 'https://alive.example',
        'provider' => 'generic', 'status' => 'failed',
    ]);
    $stale($referenced);
    $referenced->linkedUrls()->create(['linkable_type' => 'post', 'linkable_id' => $post->id, 'position' => 0]);

    $this->artisan('feed:prune')->assertSuccessful();

    expect(FeedSeen::query()->pluck('id'))->toContain($fresh->id)->toHaveCount(1)
        ->and(LinkPreview::query()->pluck('id'))->toContain($referenced->id)
        ->not->toContain($dead->id);
});
