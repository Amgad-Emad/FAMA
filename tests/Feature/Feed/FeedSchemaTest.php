<?php

use App\Models\Brand;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\Like;
use App\Models\LinkedUrl;
use App\Models\LinkPreview;
use App\Models\Mention;
use App\Models\Post;
use App\Models\PostReport;
use App\Models\Project;
use App\Models\Talent;
use App\Models\TalentType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// ----- Morph map (ADR-F1) ----------------------------------------------------

it('stores feed morphs under enforced aliases, never FQCNs', function () {
    $post = Post::factory()->create();

    expect($post->author_type)->toBe('talent')
        ->and(DB::table('posts')->value('author_type'))->toBe('talent')
        ->and((new Talent)->getMorphClass())->toBe('talent')
        ->and((new Brand)->getMorphClass())->toBe('brand')
        ->and((new User)->getMorphClass())->toBe('user')
        ->and((new Post)->getMorphClass())->toBe('post')
        ->and((new Comment)->getMorphClass())->toBe('comment')
        ->and((new Project)->getMorphClass())->toBe('project');
});

// ----- Relations -------------------------------------------------------------

it('wires the post graph: author, skills, comments, likes, mentions, links, reports', function () {
    $talent = Talent::factory()->create();
    $skill = TalentType::query()->first() ?? TalentType::factory()->create();
    $post = Post::factory()->byAuthor($talent)->create();
    $post->skills()->attach($skill);

    $comment = Comment::factory()->byAuthor($talent)->create(['post_id' => $post->id]);
    Like::factory()->on($post)->by($talent)->create();
    Mention::query()->create([
        'mentionable_type' => 'post', 'mentionable_id' => $post->id,
        'mentioned_type' => 'talent', 'mentioned_id' => $talent->id,
        'start_offset' => 0, 'length' => 5,
    ]);
    $preview = LinkPreview::query()->create([
        'url_hash' => hash('sha256', 'https://instagram.com/fama'),
        'url' => 'https://instagram.com/fama',
        'provider' => 'instagram', 'provider_handle' => 'fama', 'status' => 'pending',
    ]);
    LinkedUrl::query()->create([
        'linkable_type' => 'post', 'linkable_id' => $post->id,
        'link_preview_id' => $preview->id, 'position' => 0,
    ]);
    PostReport::query()->create([
        'reportable_type' => 'post', 'reportable_id' => $post->id,
        'reporter_type' => 'talent', 'reporter_id' => $talent->id,
        'reason' => 'spam', 'status' => 'open',
    ]);

    $post->refresh();

    expect($post->author)->toBeInstanceOf(Talent::class)
        ->and($post->author->id)->toBe($talent->id)
        ->and($post->skills)->toHaveCount(1)
        ->and($post->comments)->toHaveCount(1)
        ->and($post->likes)->toHaveCount(1)
        ->and($post->mentions)->toHaveCount(1)
        ->and($post->linkedUrls)->toHaveCount(1)
        ->and($post->linkedUrls->first()->preview->provider)->toBe('instagram')
        ->and($post->reports)->toHaveCount(1)
        ->and($comment->post->id)->toBe($post->id);
});

it('gives talents and brands the shared feed identity (posts, followers, following)', function () {
    $talent = Talent::factory()->create();
    $other = Talent::factory()->create();
    $brand = Brand::factory()->create();

    Post::factory()->byAuthor($talent)->count(2)->create();
    Follow::factory()->by($other)->of($talent)->create();
    Follow::factory()->by($brand)->of($talent)->create();

    expect($talent->posts)->toHaveCount(2)
        ->and($talent->followers)->toHaveCount(2)
        ->and($other->following)->toHaveCount(1)
        ->and($brand->following)->toHaveCount(1)
        ->and($talent->feedUsername())->toBe($talent->slug)
        ->and($brand->feedDisplayName())->toBe($brand->name);
});

it('links a project_share to its Project and keeps the snapshot when the Project dies', function () {
    $talent = Talent::factory()->create();
    $project = Project::factory()->create(['talent_id' => $talent->id, 'title' => ['en' => 'Retro Editorial']]);
    $post = Post::factory()->byAuthor($talent)->sharingProject($project)->create();

    expect($post->project->id)->toBe($project->id)
        ->and($post->shared_snapshot['title'] ?? null)->not->toBeNull();

    $project->delete();
    $post->refresh();

    // Live reference gone, frozen snapshot intact (ADR-F2).
    expect($post->project_id)->toBeNull()
        ->and($post->shared_snapshot['title'])->not->toBeNull();
});

// ----- Constraints -----------------------------------------------------------

it('rejects a duplicate like at the database level (idempotency backstop)', function () {
    $like = Like::factory()->create();

    expect(fn () => Like::factory()->create([
        'likeable_type' => $like->likeable_type,
        'likeable_id' => $like->likeable_id,
        'liker_type' => $like->liker_type,
        'liker_id' => $like->liker_id,
    ]))->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});

it('rejects a duplicate follow edge at the database level', function () {
    $follow = Follow::factory()->create();

    expect(fn () => Follow::factory()->create([
        'follower_type' => $follow->follower_type,
        'follower_id' => $follow->follower_id,
        'followable_type' => $follow->followable_type,
        'followable_id' => $follow->followable_id,
    ]))->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});

// ----- Edit window (D11) -----------------------------------------------------

it('locks posts and comments after the 15-minute edit window', function () {
    $fresh = Post::factory()->create(['published_at' => now()->subMinutes(5)]);
    $stale = Post::factory()->create(['published_at' => now()->subMinutes(20)]);
    $freshComment = Comment::factory()->create();
    $staleComment = Comment::factory()->create(['created_at' => now()->subMinutes(20)]);

    expect($fresh->isEditable())->toBeTrue()
        ->and($stale->isEditable())->toBeFalse()
        ->and($freshComment->isEditable())->toBeTrue()
        ->and($staleComment->isEditable())->toBeFalse();
});
