<?php

use App\Models\Comment;
use App\Models\PortfolioItem;
use App\Models\Post;
use App\Models\Project;
use App\Models\Talent;
use App\Models\TalentType;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\TalentTypeSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Deleting anything that holds files removes the files: hard deletes at once
 * (medialibrary's deleting hook); soft deletes after the restore window
 * (media:purge-trashed); orphans whose owner row vanished are swept too.
 */
beforeEach(function () {
    Storage::fake('public');
    $this->seed([TalentTypeSeeder::class, BlockTypeSeeder::class]);
    $this->talent = Talent::factory()->create();
    $skill = TalentType::where('slug', 'modeling')->firstOrFail();
    $this->talent->talentTypes()->attach($skill->id, ['is_primary' => true, 'position' => 0]);
    $this->skill = $skill;
});

/** Stored file paths (relative to the disk) for a media row. */
function storedPath(Media $media): string
{
    return $media->getPathRelativeToRoot();
}

it('deletes a project\'s cover and gallery files with the project', function () {
    $project = Project::factory()->create(['talent_id' => $this->talent->id, 'talent_type_id' => $this->skill->id]);
    $cover = $project->addMedia(UploadedFile::fake()->image('c.jpg'))->toMediaCollection('cover');
    $shot = $project->addMedia(UploadedFile::fake()->image('g.jpg'))->toMediaCollection('media');
    Storage::disk('public')->assertExists([storedPath($cover), storedPath($shot)]);

    $this->actingAs($this->talent, 'talent')->deleteJson(route('talent.projects.destroy', $project))->assertOk();

    Storage::disk('public')->assertMissing([storedPath($cover), storedPath($shot)]);
    expect(Media::whereIn('id', [$cover->id, $shot->id])->exists())->toBeFalse();
});

it('deletes a removed project photo\'s file', function () {
    $project = Project::factory()->create(['talent_id' => $this->talent->id, 'talent_type_id' => $this->skill->id]);
    $shot = $project->addMedia(UploadedFile::fake()->image('g.jpg'))->toMediaCollection('media');

    $this->actingAs($this->talent, 'talent')->deleteJson(route('talent.projects.media.destroy', [$project, $shot->id]))->assertOk();

    Storage::disk('public')->assertMissing(storedPath($shot));
});

it('deletes a profile-content item\'s file with the item', function () {
    $item = PortfolioItem::factory()->create(['talent_id' => $this->talent->id]);
    $media = $item->addMedia(UploadedFile::fake()->image('p.jpg'))->toMediaCollection('gallery');

    $item->delete();

    Storage::disk('public')->assertMissing(storedPath($media));
});

it('keeps a soft-deleted post\'s files inside the restore window, then purges the post for good', function () {
    $post = Post::factory()->create(['author_type' => 'talent', 'author_id' => $this->talent->id]);
    $media = $post->addMedia(UploadedFile::fake()->image('f.jpg'))->toMediaCollection('post_media');
    $post->delete();

    $this->artisan('media:purge-trashed')->assertSuccessful();
    Storage::disk('public')->assertExists(storedPath($media));

    $post->forceFill(['deleted_at' => now()->subDays(31)])->saveQuietly();
    $this->artisan('media:purge-trashed')->assertSuccessful();

    Storage::disk('public')->assertMissing(storedPath($media));
    expect(Post::withTrashed()->find($post->id))->toBeNull();
});

it('sweeps orphaned media whose owner row is gone', function () {
    $item = PortfolioItem::factory()->create(['talent_id' => $this->talent->id]);
    $media = $item->addMedia(UploadedFile::fake()->image('o.jpg'))->toMediaCollection('gallery');
    DB::table('portfolio_items')->where('id', $item->id)->delete(); // a cascade: no model events

    $this->artisan('media:purge-trashed')->assertSuccessful();

    Storage::disk('public')->assertMissing(storedPath($media));
    expect(Media::find($media->id))->toBeNull();
});

it('reports without deleting on --dry-run', function () {
    $item = PortfolioItem::factory()->create(['talent_id' => $this->talent->id]);
    $media = $item->addMedia(UploadedFile::fake()->image('o.jpg'))->toMediaCollection('gallery');
    DB::table('portfolio_items')->where('id', $item->id)->delete();

    $this->artisan('media:purge-trashed --dry-run')->expectsOutputToContain('0 file(s) of items trashed over 30 days, and 1 orphaned')->assertSuccessful();
    expect(Media::find($media->id))->not->toBeNull();
});

it('soft-deletes a post\'s comments with the post', function () {
    $post = Post::factory()->create(['author_type' => 'talent', 'author_id' => $this->talent->id]);
    $comments = Comment::factory()->count(2)->create(['post_id' => $post->id]);

    $post->delete();

    expect(Comment::whereIn('id', $comments->pluck('id'))->count())->toBe(0)
        ->and(Comment::onlyTrashed()->whereIn('id', $comments->pluck('id'))->count())->toBe(2);
});

it('purges a long-trashed post with its comments, likes, mentions and files', function () {
    $post = Post::factory()->create(['author_type' => 'talent', 'author_id' => $this->talent->id]);
    $media = $post->addMedia(UploadedFile::fake()->image('f.jpg'))->toMediaCollection('post_media');
    $comment = Comment::factory()->create(['post_id' => $post->id]);
    DB::table('likes')->insert([
        ['likeable_type' => 'post', 'likeable_id' => $post->id, 'liker_type' => 'talent', 'liker_id' => $this->talent->id, 'created_at' => now(), 'updated_at' => now()],
        ['likeable_type' => 'comment', 'likeable_id' => $comment->id, 'liker_type' => 'talent', 'liker_id' => $this->talent->id, 'created_at' => now(), 'updated_at' => now()],
    ]);
    $post->delete();
    $post->forceFill(['deleted_at' => now()->subDays(40)])->saveQuietly();

    $this->artisan('media:purge-trashed')->assertSuccessful();

    expect(Post::withTrashed()->find($post->id))->toBeNull()
        ->and(Comment::withTrashed()->find($comment->id))->toBeNull()
        ->and(DB::table('likes')->whereIn('likeable_type', ['post', 'comment'])->whereIn('likeable_id', [$post->id, $comment->id])->count())->toBe(0);
    Storage::disk('public')->assertMissing(storedPath($media));
});
