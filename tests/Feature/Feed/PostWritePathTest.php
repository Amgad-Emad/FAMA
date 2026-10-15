<?php

use App\Models\Brand;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Project;
use App\Models\Talent;
use App\Models\TalentType;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\FeedSeeder;
use Database\Seeders\TalentTypeSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function publishedTalentWithSkill(): array
{
    $talent = Talent::factory()->create();
    $skill = TalentType::query()->first() ?? TalentType::factory()->create();
    $talent->talentTypes()->attach($skill->id, ['is_primary' => true, 'position' => 0]);

    return [$talent, $skill];
}

// ----- Create ---------------------------------------------------------------

it('lets a talent publish a note with mentions, links and own skills', function () {
    [$talent, $skill] = publishedTalentWithSkill();
    $mentioned = Talent::factory()->create(['slug' => 'rana']);

    $response = $this->actingAs($talent, 'talent')
        ->postJson('/posts', [
            'post_type' => 'note',
            'body' => 'New editorial with @rana — https://instagram.com/rana',
            'skill_ids' => [$skill->id],
        ])
        ->assertCreated();

    $post = Post::query()->findOrFail($response->json('data.id'));

    expect($post->state->getValue())->toBe('published')
        ->and($post->published_at)->not->toBeNull()
        ->and($post->skills()->count())->toBe(1)
        ->and($post->mentions()->count())->toBe(1)
        ->and($post->linkedUrls()->count())->toBe(1)
        ->and($talent->refresh()->posts_count)->toBe(1);
});

it('lets a talent publish a media post through the media library', function () {
    Storage::fake('public');
    [$talent] = publishedTalentWithSkill();

    $response = $this->actingAs($talent, 'talent')
        ->postJson('/posts', [
            'post_type' => 'media',
            'body' => 'Two frames from the Gouna set',
            'media' => [
                UploadedFile::fake()->image('one.jpg', 1200, 1500),
                UploadedFile::fake()->image('two.webp', 1080, 810),
            ],
        ])
        ->assertCreated();

    $post = Post::query()->findOrFail($response->json('data.id'));

    expect($post->getMedia('post_media'))->toHaveCount(2);
});

it('forbids brands from posting (policy, ADR-F1)', function () {
    $brand = Brand::factory()->create();

    $this->actingAs($brand, 'brand')
        ->postJson('/posts', ['post_type' => 'note', 'body' => 'We are hiring'])
        ->assertStatus(403);
});

it('rejects substance-free and oversized posts', function () {
    [$talent] = publishedTalentWithSkill();

    // Empty note.
    $this->actingAs($talent, 'talent')
        ->postJson('/posts', ['post_type' => 'note', 'body' => '  '])
        ->assertStatus(422);

    // Media post without media.
    $this->actingAs($talent, 'talent')
        ->postJson('/posts', ['post_type' => 'media'])
        ->assertStatus(422);

    // Eleven media items.
    Storage::fake('public');
    $this->actingAs($talent, 'talent')
        ->postJson('/posts', [
            'post_type' => 'media',
            'media' => collect(range(1, 11))->map(fn ($i) => UploadedFile::fake()->image("f{$i}.jpg"))->all(),
        ])
        ->assertStatus(422);
});

it('rejects tagging skills the author does not have, with a clear message', function () {
    [$talent] = publishedTalentWithSkill();
    $foreign = TalentType::factory()->create(['slug' => 'foreign-skill', 'name' => ['en' => 'Foreign']]);

    $this->actingAs($talent, 'talent')
        ->postJson('/posts', ['post_type' => 'note', 'body' => 'x', 'skill_ids' => [$foreign->id]])
        ->assertStatus(422)
        ->assertJsonPath('errors.skill_ids.0', __('You can only tag your own Skills on a post.'));
});

// ----- Project share (ADR-F2) ------------------------------------------------

it('shares an own Project with a frozen snapshot', function () {
    [$talent, $skill] = publishedTalentWithSkill();
    $project = Project::factory()->create([
        'talent_id' => $talent->id,
        'talent_type_id' => $skill->id,
        'title' => ['en' => 'Desert Editorial', 'ar' => 'إطلالة الصحراء'],
    ]);

    $response = $this->actingAs($talent, 'talent')
        ->postJson("/projects/{$project->id}/share", ['body' => 'From this spring'])
        ->assertCreated();

    $post = Post::query()->findOrFail($response->json('data.id'));

    expect($post->post_type)->toBe('project_share')
        ->and($post->project_id)->toBe($project->id)
        ->and($post->shared_snapshot['title']['en'])->toBe('Desert Editorial')
        ->and($post->shared_snapshot['shared_at'])->not->toBeNull();
});

it('refuses sharing someone else\'s Project or sharing from an unpublished profile', function () {
    [$talent] = publishedTalentWithSkill();
    $other = Talent::factory()->create();
    $notMine = Project::factory()->create(['talent_id' => $other->id]);

    $this->actingAs($talent, 'talent')
        ->postJson("/projects/{$notMine->id}/share")
        ->assertStatus(422);

    $draft = Talent::factory()->draft()->create();
    $own = Project::factory()->create(['talent_id' => $draft->id]);

    $this->actingAs($draft, 'talent')
        ->postJson("/projects/{$own->id}/share")
        ->assertStatus(422);
});

// ----- Update / delete / pin -------------------------------------------------

it('allows edits inside the window and locks after 15 minutes', function () {
    [$talent] = publishedTalentWithSkill();
    Talent::factory()->create(['slug' => 'hossam']);

    $fresh = Post::factory()->byAuthor($talent)->create(['published_at' => now()->subMinutes(5)]);

    $this->actingAs($talent, 'talent')
        ->patchJson("/posts/{$fresh->id}", ['body' => 'Edited — with @hossam'])
        ->assertOk();

    expect($fresh->refresh()->edited_at)->not->toBeNull()
        ->and($fresh->mentions()->count())->toBe(1);

    $stale = Post::factory()->byAuthor($talent)->create(['published_at' => now()->subMinutes(20)]);

    $this->actingAs($talent, 'talent')
        ->patchJson("/posts/{$stale->id}", ['body' => 'Too late'])
        ->assertStatus(403);
});

it('soft-deletes a post, uncounting it and soft-deleting its comments with it', function () {
    [$talent] = publishedTalentWithSkill();
    $post = Post::factory()->byAuthor($talent)->create();
    Comment::factory()->count(2)->create(['post_id' => $post->id]);

    expect($talent->refresh()->posts_count)->toBe(1);

    $this->actingAs($talent, 'talent')
        ->deleteJson("/posts/{$post->id}")
        ->assertOk();

    expect(Post::withTrashed()->find($post->id)->deleted_at)->not->toBeNull()
        ->and(Comment::query()->where('post_id', $post->id)->count())->toBe(0)
        ->and(Comment::onlyTrashed()->where('post_id', $post->id)->count())->toBe(2)
        ->and($talent->refresh()->posts_count)->toBe(0);
});

it('keeps at most one pinned post per talent', function () {
    [$talent] = publishedTalentWithSkill();
    $first = Post::factory()->byAuthor($talent)->create();
    $second = Post::factory()->byAuthor($talent)->create();

    $this->actingAs($talent, 'talent')->postJson("/posts/{$first->id}/pin")->assertOk();
    $this->actingAs($talent, 'talent')->postJson("/posts/{$second->id}/pin")
        ->assertJsonPath('data.pinned', true);

    expect($first->refresh()->is_pinned)->toBeFalse()
        ->and($second->refresh()->is_pinned)->toBeTrue();

    // Toggling off.
    $this->actingAs($talent, 'talent')->postJson("/posts/{$second->id}/pin")
        ->assertJsonPath('data.pinned', false);
});

// ----- Seeder ----------------------------------------------------------------

it('seeds a consistent, counter-accurate feed dataset', function () {
    config(['feed.seeder' => ['talents' => 6, 'brands' => 3, 'posts' => 18]]);
    $this->seed(TalentTypeSeeder::class);
    $this->seed(BlockTypeSeeder::class);

    $this->seed(FeedSeeder::class);

    expect(Post::query()->count())->toBeGreaterThanOrEqual(15)
        ->and(Post::query()->where('post_type', 'media')->exists())->toBeTrue();

    // A second reconcile finds zero drift — the seeder left counters exact.
    $repaired = app(App\Services\CounterService::class)->reconcile();

    expect(array_sum($repaired))->toBe(0);
});
