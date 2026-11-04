<?php

use App\Actions\Feed\ToggleBookmark;
use App\Actions\Feed\ToggleFollow;
use App\Models\Post;
use App\Models\Talent;
use App\Models\TalentType;
use App\Notifications\Feed\MentionedInPost;
use App\Notifications\Feed\NewFollower;
use App\Services\BodyRenderer;
use App\Support\SkillTags;

// fama-ui feed (2026-10-12): the behaviour behind the redesigned surfaces.

it('renders the guest feed with the way in inside the hero and no composer', function () {
    Post::factory()->count(2)->create();

    $html = $this->get('/feed')->assertOk()->getContent();

    expect($html)->toContain('fx-hero')
        ->toContain(route('register'))
        ->toContain('Join as talent')
        ->toContain('id="filterDlg"')
        ->toContain('class="signdlg"')            // the guest sign-in prompt
        ->not->toContain('class="composer"');
});

it('turns a #SkillTag in a post into a link to the filtered feed and drops URLs from the text', function () {
    $skill = TalentType::factory()->create(['name' => ['en' => 'Hair & makeup'], 'slug' => 'hair-makeup-x']);
    $post = Post::factory()->create(['body' => 'Looking for #HairMakeup people — see https://example.com/brief']);
    $post->load(['mentions.mentioned', 'linkedUrls.preview']);

    $html = app(BodyRenderer::class)->renderText($post);

    expect(SkillTags::tag('Hair & makeup'))->toBe('HairMakeup')
        ->and($html)->toContain('href="'.e(route('feed', ['skill' => $skill->slug])).'"')
        ->and($html)->toContain('#HairMakeup</a>')
        ->and($html)->not->toContain('https://example.com');
});

it('leaves HTML entities and unknown hashtags alone', function () {
    expect(SkillTags::linkify(e("It's #NotASkill")))->toBe(e("It's #NotASkill"));
});

it('filters the notifications inbox to unread rows and to mentions', function () {
    config(['queue.default' => 'sync']);
    $me = Talent::factory()->create();
    $fan = Talent::factory()->create();

    $payload = fn (string $key) => ['message' => $key, 'params' => [], 'url' => '/feed', 'actor' => ['name' => $fan->display_name, 'username' => $fan->slug]];
    $me->notify(new NewFollower($payload(':name followed you')));
    $me->notify(new MentionedInPost($payload(':name mentioned you')));
    $me->notifications()->where('type', NewFollower::class)->update(['read_at' => now()]);

    $this->actingAs($me, 'talent');

    expect($this->getJson('/notifications')->json('data'))->toHaveCount(2)
        ->and($this->getJson('/notifications?filter=unread')->json('data'))->toHaveCount(1)
        ->and(collect($this->getJson('/notifications?filter=mention')->json('data'))->pluck('type')->all())->toBe(['MentionedInPost']);

    $this->get(route('notifications.page'))->assertOk()->assertSee('famaNotifications(', false);
});

it('serves the Activity Saved list as canonical post cards', function () {
    $me = Talent::factory()->create();
    $post = Post::factory()->create();
    app(ToggleBookmark::class)($me, $post, onlySave: true);

    $rows = $this->actingAs($me, 'talent')
        ->getJson(route('talent.feed.collection.data', ['mode' => 'saved', 'format' => 'html']))
        ->assertOk()->json('data');

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['html'])->toContain('id="post-'.$post->id.'"')
        ->and($rows[0]['html'])->toContain('aria-pressed="true"'); // the viewer's save state rides along

    // An empty list still answers (no eager-load on a plain collection).
    $this->actingAs(Talent::factory()->create(), 'talent')
        ->getJson(route('talent.feed.collection.data', ['mode' => 'saved', 'format' => 'html']))
        ->assertOk()->assertJsonCount(0, 'data');
});

it('renders the Activity page with live tab counts and both sides of the follow graph', function () {
    $me = Talent::factory()->create();
    $other = Talent::factory()->create();
    app(ToggleFollow::class)($me, $other);

    $this->actingAs($me->fresh(), 'talent')
        ->get(route('talent.feed.activity'))
        ->assertOk()
        ->assertViewHas('view', 'saved') // opens on Saved, as the prototype does
        ->assertViewHas('counts', fn (array $c) => $c['following'] === 1 && $c['saved'] === 0)
        ->assertSee('famaActivity(', false);
});

it('marks people the viewer follows in mention suggestions', function () {
    $me = Talent::factory()->create();
    $friend = Talent::factory()->create(['slug' => 'nadia-follow', 'display_name' => 'Nadia', 'is_published' => true]);
    app(ToggleFollow::class)($me, $friend);

    $row = collect($this->actingAs($me, 'talent')->getJson('/mentions/search?q=nadia')->json('data.talents'))->firstWhere('id', $friend->id);

    expect($row['followed'])->toBeTrue();
});

it('shows a signed-in admin as signed in on the feed, never the visitor log-in', function () {
    $admin = \App\Models\User::factory()->create(['name' => 'Staff Person']);
    Post::factory()->count(2)->create();

    $html = $this->actingAs($admin, 'admin')->get('/feed')->assertOk()->getContent();

    expect($html)
        ->toContain('Staff Person')
        ->toContain(route('admin.dashboard'))
        ->toContain('window.famaStaff = true')
        ->toContain('Signed in as Fama staff')
        ->not->toContain('Creative talent on Fama')
        ->not->toContain('fx-login');
});
