<?php

use App\Actions\Feed\CreateComment;
use App\Actions\Feed\ShareProjectAsPost;
use App\Actions\Feed\ToggleFollow;
use App\Actions\Feed\ToggleLike;
use App\Models\Brand;
use App\Models\Post;
use App\Models\Project;
use App\Models\Talent;
use App\Models\TalentType;
use App\Notifications\Feed\CommentReplied;
use App\Notifications\Feed\MentionedInComment;
use App\Notifications\Feed\NewFollower;
use App\Notifications\Feed\PostCommented;
use App\Notifications\Feed\PostLiked;
use App\Notifications\Feed\ProjectShared;

beforeEach(function () {
    // Notifications carry no network work — process them inline so the
    // database rows are assertable (the global test queue stays `database`).
    config(['queue.default' => 'sync']);
});

// ----- Likes: self-guard + aggregation ---------------------------------------

it('notifies the post author on a like, never on a self-like, and aggregates repeats', function () {
    $author = Talent::factory()->create();
    $post = Post::factory()->byAuthor($author)->create();

    // Self-like: no notification.
    app(ToggleLike::class)($author, $post, onlyLike: true);
    expect($author->notifications()->count())->toBe(0);

    $fanA = Talent::factory()->create(['display_name' => 'Rana']);
    $fanB = Talent::factory()->create(['display_name' => 'Hossam']);

    app(ToggleLike::class)($fanA, $post, onlyLike: true);
    app(ToggleLike::class)($fanB, $post, onlyLike: true);

    $notifications = $author->notifications()->where('type', PostLiked::class)->get();

    expect($notifications)->toHaveCount(1)
        ->and($notifications->first()->data['actors_count'])->toBe(2)
        ->and($notifications->first()->data['params']['name'])->toBe('Hossam')
        ->and($notifications->first()->data['url'])->toContain("/posts/{$post->id}");
});

// ----- Comments: mention suppression -----------------------------------------

it('sends a mention notification INSTEAD of the comment notification for the same event', function () {
    $author = Talent::factory()->create(['slug' => 'author-rana']);
    $post = Post::factory()->byAuthor($author)->create();
    $commenter = Talent::factory()->create();

    app(CreateComment::class)($commenter, $post, 'Beautiful work @author-rana');

    expect($author->notifications()->where('type', MentionedInComment::class)->count())->toBe(1)
        ->and($author->notifications()->where('type', PostCommented::class)->count())->toBe(0);
});

it('notifies post author and parent author on a reply, each once', function () {
    $author = Talent::factory()->create();
    $post = Post::factory()->byAuthor($author)->create();
    $parentAuthor = Brand::factory()->create();
    $replier = Talent::factory()->create();

    $parent = app(CreateComment::class)($parentAuthor, $post, 'We love this direction');
    app(CreateComment::class)($replier, $post, 'Agreed!', $parent);

    expect($author->notifications()->where('type', PostCommented::class)->count())->toBe(2)
        ->and($parentAuthor->notifications()->where('type', CommentReplied::class)->count())->toBe(1);
});

// ----- Follows ---------------------------------------------------------------

it('notifies on a NEW follow only — refollow inside the window stays silent', function () {
    $talent = Talent::factory()->create();
    $fan = Talent::factory()->create();

    $toggle = app(ToggleFollow::class);
    $toggle($fan, $talent, onlyFollow: true);
    $toggle($fan, $talent, onlyUnfollow: true);
    $toggle($fan, $talent, onlyFollow: true); // refollow

    expect($talent->notifications()->where('type', NewFollower::class)->count())->toBe(1);
});

// ----- Project share fan-out -------------------------------------------------

it('fans a Project share out to the author\'s followers', function () {
    $author = Talent::factory()->create();
    $skill = TalentType::query()->first() ?? TalentType::factory()->create();
    $author->talentTypes()->attach($skill->id, ['is_primary' => true, 'position' => 0]);
    $project = Project::factory()->create(['talent_id' => $author->id]);

    $fans = Talent::factory()->count(3)->create();
    $fans->each(fn ($fan) => app(ToggleFollow::class)($fan, $author, onlyFollow: true));

    app(ShareProjectAsPost::class)($author, $project, 'Fresh from the studio');

    $fans->each(function ($fan) {
        expect($fan->notifications()->where('type', ProjectShared::class)->count())->toBe(1);
    });

    // The author gets nothing from their own share.
    expect($author->notifications()->where('type', ProjectShared::class)->count())->toBe(0);
});

// ----- Bilingual rendering ---------------------------------------------------

it('renders notification content in both en and ar', function () {
    $params = ['name' => 'Rana'];

    app()->setLocale('en');
    expect(__(':name liked your post', $params))->toBe('Rana liked your post');

    app()->setLocale('ar');
    expect(__(':name liked your post', $params))->toBe('Rana أُعجب بمنشورك')
        ->and(__(':name started following you', $params))->toBe('Rana بدأ بمتابعتك');

    app()->setLocale('en');
});

// ----- Endpoints -------------------------------------------------------------

it('serves the inbox, unread count and mark-read to the actor only', function () {
    $talent = Talent::factory()->create();
    $post = Post::factory()->byAuthor($talent)->create();
    $fan = Talent::factory()->create(['display_name' => 'Laila']);
    app(ToggleLike::class)($fan, $post, onlyLike: true);

    $this->getJson('/notifications')->assertStatus(401);

    $index = $this->actingAs($talent, 'talent')->getJson('/notifications')->assertOk();

    expect($index->json('data.0.message'))->toBe('Laila liked your post')
        ->and($index->json('meta.pagination.total'))->toBe(1);

    $this->actingAs($talent, 'talent')->getJson('/notifications/unread-count')
        ->assertJsonPath('data.count', 1);

    $this->actingAs($talent, 'talent')->postJson('/notifications/read')->assertOk();

    $this->actingAs($talent, 'talent')->getJson('/notifications/unread-count')
        ->assertJsonPath('data.count', 0);
});
