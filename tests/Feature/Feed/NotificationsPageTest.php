<?php

use App\Actions\Feed\CreateComment;
use App\Actions\Feed\ToggleFollow;
use App\Models\Post;
use App\Models\Project;
use App\Models\Talent;
use App\Notifications\Feed\MentionedInPost;
use App\Notifications\Feed\NewFollower;
use App\Notifications\Feed\PostLiked;
use App\Services\NotificationInboxService;

/**
 * The fama-ui notifications page redesign (notifications.html, 1 Nov 2026)
 * and the Activity page refresh (following.html): pill counts, the kind that
 * picks each card's one action, the one-line excerpt, Follow back state, and
 * the Activity rows' work strip + default tab.
 */
beforeEach(function () {
    // Notifications carry no network work — write them inline.
    config(['queue.default' => 'sync']);
});

/** A minimal stored notification payload from $actor. */
function inboxPayload(Talent $actor, string $message): array
{
    return [
        'message' => $message, 'params' => ['name' => $actor->display_name], 'url' => '/feed',
        'actor' => ['type' => 'talent', 'id' => $actor->id, 'username' => $actor->slug, 'name' => $actor->display_name],
    ];
}

it('returns the All / Unread / Mentions pill counts with every page', function () {
    $me = Talent::factory()->create();
    $fan = Talent::factory()->create();

    $me->notify(new NewFollower(inboxPayload($fan, ':name started following you')));
    $me->notify(new MentionedInPost(inboxPayload($fan, ':name mentioned you in a post')));
    $me->notify(new PostLiked(inboxPayload($fan, ':name liked your post')));
    $me->notifications()->where('type', PostLiked::class)->update(['read_at' => now()]);

    $this->actingAs($me, 'talent');

    $this->getJson('/notifications')->assertOk()
        ->assertJsonPath('meta.counts', ['all' => 3, 'unread' => 2, 'mention' => 1]);

    // The counts describe the whole inbox, whichever pill is showing.
    $this->getJson('/notifications?filter=mention')->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.counts.all', 3);
});

it('tags each row with the kind its card acts on', function () {
    expect(NotificationInboxService::kind(MentionedInPost::class))->toBe('mention')
        ->and(NotificationInboxService::kind(NewFollower::class))->toBe('follow')
        ->and(NotificationInboxService::kind(PostLiked::class))->toBe('like')
        ->and(NotificationInboxService::kind('App\\Notifications\\Credits\\ProjectCreditRequested'))->toBe('credit')
        ->and(NotificationInboxService::kind('App\\Notifications\\Credits\\ProjectCreditAccepted'))->toBe('credit-ok')
        ->and(NotificationInboxService::kind('App\\Notifications\\Feed\\ContentRemoved'))->toBe('system');
});

it('stores a one-line excerpt of what was said on comment and mention notifications', function () {
    $author = Talent::factory()->create(['slug' => 'excerpt-author']);
    $post = Post::factory()->byAuthor($author)->create();
    $commenter = Talent::factory()->create();

    app(CreateComment::class)($commenter, $post, "That light\n\nis unreal.");
    app(CreateComment::class)($commenter, $post, 'Saving this @excerpt-author');

    $rows = collect($this->actingAs($author, 'talent')->getJson('/notifications')->assertOk()->json('data'));

    expect($rows->firstWhere('kind', 'comment')['excerpt'])->toBe('That light is unreal.')
        ->and($rows->firstWhere('kind', 'mention')['excerpt'])->toBe('Saving this @excerpt-author');
});

it('says on a new-follower row whether the viewer already follows them back', function () {
    $me = Talent::factory()->create();
    $mutual = Talent::factory()->create();
    $stranger = Talent::factory()->create();

    app(ToggleFollow::class)($mutual, $me);
    app(ToggleFollow::class)($stranger, $me);
    app(ToggleFollow::class)($me, $mutual);

    $rows = collect($this->actingAs($me, 'talent')->getJson('/notifications')->assertOk()->json('data'))
        ->where('kind', 'follow')
        ->keyBy(fn (array $row) => $row['actor']['id']);

    expect($rows[$mutual->id]['followed_back'])->toBeTrue()
        ->and($rows[$stranger->id]['followed_back'])->toBeFalse();
});

it('renders the notifications page as pill tabs and cards', function () {
    $html = $this->actingAs(Talent::factory()->create(), 'talent')
        ->get(route('notifications.page'))->assertOk()->getContent();

    expect($html)->toContain('class="ptabs"')
        ->toContain('class="nc"')
        ->toContain('famaNotifications(')
        ->not->toContain('class="seg"');
});

it('opens Activity on Saved and gives follow rows a work strip from their latest projects', function () {
    $me = Talent::factory()->create();
    $followed = Talent::factory()->create(['is_published' => true]);
    app(ToggleFollow::class)($me, $followed);
    $project = Project::factory()->create(['talent_id' => $followed->id]);
    $project->addMedia(\Illuminate\Http\UploadedFile::fake()->image('cover.jpg'))->toMediaCollection('cover');

    $this->actingAs($me->fresh(), 'talent')
        ->get(route('talent.feed.activity'))
        ->assertOk()
        ->assertViewHas('view', 'saved');

    $row = collect($this->getJson('/talent/feed/follows/data?tab=following')->assertOk()->json('data'))
        ->firstWhere('id', $followed->id);

    expect($row['work'])->toHaveCount(1)
        ->and($row['work'][0])->toContain('cover');
});

it('keeps a private portfolio out of the work strip', function () {
    $me = Talent::factory()->create();
    $private = Talent::factory()->create(['is_published' => false]);
    app(ToggleFollow::class)($me, $private);
    $project = Project::factory()->create(['talent_id' => $private->id]);
    $project->addMedia(\Illuminate\Http\UploadedFile::fake()->image('cover.jpg'))->toMediaCollection('cover');

    $row = collect($this->actingAs($me, 'talent')->getJson('/talent/feed/follows/data?tab=following')->assertOk()->json('data'))
        ->firstWhere('id', $private->id);

    expect($row['work'])->toBe([]);
});
