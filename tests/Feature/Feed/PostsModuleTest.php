<?php

use App\Mail\ContentRemovedNotice;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\States\Post\Hidden;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Mail;
use Spatie\Activitylog\Models\Activity;

/** The admin Posts module: bulk hide / unhide / delete, single delete, rich rows. */
beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
});

it('bulk hides posts and skips the ones already hidden', function () {
    $posts = Post::factory()->count(3)->create();
    $posts[2]->state->transitionTo(Hidden::class);

    $this->actingAs($this->admin, 'admin')
        ->postJson('/admin/moderation/feed/bulk', ['kind' => 'post', 'action' => 'hide', 'ids' => $posts->pluck('id')->all()])
        ->assertOk()->assertJsonPath('data.done', 2)->assertJsonPath('data.skipped', 1);

    expect(Post::where('state', 'hidden')->count())->toBe(3)
        ->and(Activity::where('description', 'post.hidden')->count())->toBe(2);
});

it('bulk deletes posts as audited soft deletes', function () {
    $posts = Post::factory()->count(2)->create();

    $this->actingAs($this->admin, 'admin')
        ->postJson('/admin/moderation/feed/bulk', ['kind' => 'post', 'action' => 'delete', 'ids' => $posts->pluck('id')->all()])
        ->assertOk()->assertJsonPath('data.done', 2);

    expect(Post::count())->toBe(0)->and(Post::onlyTrashed()->count())->toBe(2)
        ->and(Activity::where('description', 'post.deleted')->count())->toBe(2);
});

it('deletes a single comment', function () {
    $comment = Comment::factory()->create();

    $this->actingAs($this->admin, 'admin')
        ->patchJson("/admin/moderation/feed/comments/{$comment->id}/delete")
        ->assertOk()->assertJsonPath('data.deleted', true);

    expect(Comment::find($comment->id))->toBeNull();
});

it('lists posts with author, counts and open reports', function () {
    Post::factory()->create();

    $this->actingAs($this->admin, 'admin')->getJson('/admin/moderation/feed/posts')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'author', 'author_avatar', 'thumb', 'open_reports', 'likes', 'comments', 'ago', 'state']]]);
});

it('opens on All posts and names the module Posts', function () {
    $this->actingAs($this->admin, 'admin')->get('/admin/moderation/feed')
        ->assertOk()->assertSee('adminPosts(', false)->assertSee('All posts');
});

it('rejects an unknown bulk action', function () {
    $this->actingAs($this->admin, 'admin')
        ->postJson('/admin/moderation/feed/bulk', ['kind' => 'post', 'action' => 'remove', 'ids' => [1]])
        ->assertUnprocessable();
});

it('suggests the removal email and previews it with the reason and the post', function () {
    $post = Post::factory()->create(['body' => 'My lookbook drop']);

    $html = $this->actingAs($this->admin, 'admin')
        ->postJson("/admin/moderation/feed/posts/{$post->id}/notice", ['reason' => 'Uncredited photos'])
        ->assertOk()->assertJsonPath('data.defaults.subject', 'Your post on Fama was removed')
        ->json('data.html');

    expect($html)->toContain('Uncredited photos')->toContain('My lookbook drop');
});

it('removes a post and emails the author the composed notice', function () {
    Mail::fake();
    $post = Post::factory()->create();

    $this->actingAs($this->admin, 'admin')->patchJson("/admin/moderation/feed/posts/{$post->id}/remove", [
        'reason' => 'Uncredited photos',
        'notify' => ['send' => true, 'email' => 'author@example.com', 'subject' => 'Removed', 'message' => 'Hi.'],
    ])->assertOk();

    expect($post->fresh()->state->getValue())->toBe('removed');
    Mail::assertQueued(ContentRemovedNotice::class, fn ($m) => $m->hasTo('author@example.com') && $m->reason === 'Uncredited photos');
});

it('rejects a bad address before removing', function () {
    Mail::fake();
    $post = Post::factory()->create();

    $this->actingAs($this->admin, 'admin')->patchJson("/admin/moderation/feed/posts/{$post->id}/remove", [
        'reason' => 'x', 'notify' => ['send' => true, 'email' => 'nope', 'subject' => 'x', 'message' => 'x'],
    ])->assertUnprocessable();

    expect($post->fresh()->state->getValue())->toBe('published');
    Mail::assertNothingQueued();
});
