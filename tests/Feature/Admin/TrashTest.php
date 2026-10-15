<?php

use App\Models\Comment;
use App\Models\PortfolioItem;
use App\Models\Post;
use App\Models\Project;
use App\Models\Talent;
use App\Models\TalentType;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TalentTypeSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/** The admin Trash: list, restore, delete forever, bulk — and their rules. */
beforeEach(function () {
    Storage::fake('public');
    $this->seed([RolesAndPermissionsSeeder::class, TalentTypeSeeder::class]);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
});

it('lists trashed items per type with counts', function () {
    Talent::factory()->create()->delete();
    Post::factory()->create()->delete();

    $this->actingAs($this->admin, 'admin')->get('/admin/trash')->assertOk()->assertSee('adminTrash(', false);
    $this->getJson('/admin/trash/talents')->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.counts.posts', 1)
        ->assertJsonStructure(['data' => [['id', 'title', 'deleted_ago', 'purge_in_days']]]);
});

it('restores a post together with the comments deleted with it', function () {
    $post = Post::factory()->create();
    Comment::factory()->count(2)->create(['post_id' => $post->id]);
    $post->delete();

    $this->actingAs($this->admin, 'admin')->postJson("/admin/trash/posts/{$post->id}/restore")->assertOk();

    expect(Post::find($post->id))->not->toBeNull()
        ->and(Comment::where('post_id', $post->id)->count())->toBe(2)
        ->and(Activity::where('description', 'trash.restored')->count())->toBe(1);
});

it('refuses to restore a comment while its post is in the trash', function () {
    $post = Post::factory()->create();
    $comment = Comment::factory()->create(['post_id' => $post->id]);
    $post->delete();

    $this->actingAs($this->admin, 'admin')->postJson("/admin/trash/comments/{$comment->id}/restore")->assertStatus(422);
    expect(Comment::find($comment->id))->toBeNull();
});

it('deletes a talent forever with their posts, projects and files', function () {
    $talent = Talent::factory()->create();
    $skill = TalentType::firstOrFail();
    $project = Project::factory()->create(['talent_id' => $talent->id, 'talent_type_id' => $skill->id]);
    $file = $project->addMedia(UploadedFile::fake()->image('c.jpg'))->toMediaCollection('cover');
    $post = Post::factory()->create(['author_type' => 'talent', 'author_id' => $talent->id]);
    $talent->delete();

    $this->actingAs($this->admin, 'admin')->deleteJson("/admin/trash/talents/{$talent->id}")->assertOk();

    expect(Talent::withTrashed()->find($talent->id))->toBeNull()
        ->and(Project::find($project->id))->toBeNull()
        ->and(Post::withTrashed()->find($post->id))->toBeNull()
        ->and(Activity::where('description', 'trash.purged')->exists())->toBeTrue();
    Storage::disk('public')->assertMissing($file->getPathRelativeToRoot());
});

it('bulk restores and bulk deletes forever', function () {
    $posts = Post::factory()->count(3)->create();
    $posts->each->delete();

    $this->actingAs($this->admin, 'admin')
        ->postJson('/admin/trash/posts/bulk', ['action' => 'restore', 'ids' => [$posts[0]->id]])->assertOk()->assertJsonPath('data.done', 1);
    $this->postJson('/admin/trash/posts/bulk', ['action' => 'purge', 'ids' => [$posts[1]->id, $posts[2]->id]])->assertOk()->assertJsonPath('data.done', 2);

    expect(Post::count())->toBe(1)->and(Post::onlyTrashed()->count())->toBe(0);
});

it('lets a moderator restore but not delete forever', function () {
    $mod = User::factory()->create();
    $mod->assignRole('moderator');
    $post = Post::factory()->create();
    $post->delete();

    $this->actingAs($mod, 'admin')->deleteJson("/admin/trash/posts/{$post->id}")->assertForbidden();
    $this->postJson('/admin/trash/posts/bulk', ['action' => 'purge', 'ids' => [$post->id]])->assertForbidden();
    $this->postJson("/admin/trash/posts/{$post->id}/restore")->assertOk();
});

it('hides the brands tab while brands are off', function () {
    config(['features.brands' => false]);

    $this->actingAs($this->admin, 'admin')->getJson('/admin/trash/brands')->assertNotFound();
});

/** A trashed talent with files everywhere: avatar, project cover, portfolio item, post photo. */
function talentWithFiles(): array
{
    $talent = Talent::factory()->create();
    $skill = TalentType::firstOrFail();
    $avatar = $talent->addMedia(UploadedFile::fake()->image('a.jpg'))->toMediaCollection('avatar');
    $project = Project::factory()->create(['talent_id' => $talent->id, 'talent_type_id' => $skill->id]);
    $cover = $project->addMedia(UploadedFile::fake()->image('c.jpg'))->toMediaCollection('cover');
    $item = PortfolioItem::factory()->create(['talent_id' => $talent->id]);
    $gallery = $item->addMedia(UploadedFile::fake()->image('g.jpg'))->toMediaCollection('gallery');
    $post = Post::factory()->create(['author_type' => 'talent', 'author_id' => $talent->id]);
    $photo = $post->addMedia(UploadedFile::fake()->image('p.jpg'))->toMediaCollection('post_media');
    $talent->delete();

    return [$talent, array_map(fn ($m) => $m->getPathRelativeToRoot(), [$avatar, $cover, $gallery, $photo])];
}

it('removes every file of a talent deleted forever', function () {
    [$talent, $files] = talentWithFiles();

    $this->actingAs($this->admin, 'admin')->deleteJson("/admin/trash/talents/{$talent->id}")->assertOk();

    Storage::disk('public')->assertMissing($files);
    expect(Media::count())->toBe(0);
});

it('purges a talent left in the trash past the window, files included', function () {
    [$talent, $files] = talentWithFiles();

    $this->artisan('media:purge-trashed')->assertSuccessful();
    Storage::disk('public')->assertExists($files); // still restorable

    $talent->forceFill(['deleted_at' => now()->subDays(31)])->saveQuietly();
    $this->artisan('media:purge-trashed')->assertSuccessful();

    expect(Talent::withTrashed()->find($talent->id))->toBeNull();
    Storage::disk('public')->assertMissing($files);
    expect(Media::count())->toBe(0);
});
