<?php

namespace App\Actions\Feed;

use App\Data\Feed\CreatePostData;
use App\Models\Post;
use App\Models\Project;
use App\Models\Talent;
use App\Services\RichTextParser;
use App\States\Post\Draft;
use App\States\Post\Published;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Share a talent's own Project into the feed (feed-spec §4, ADR-F2): a
 * project_share post live-referencing the Project plus a frozen
 * shared_snapshot ({title translations, cover_media_path, shared_at}) so an
 * unpublished/deleted Project degrades to a dead-but-intact frame. Ownership
 * and public visibility (the owning profile is published — feed-spec Q2) are
 * enforced here, not in the controller.
 */
class ShareProjectAsPost
{
    public function __construct(
        private readonly RichTextParser $parser,
        private readonly SyncMentions $syncMentions,
        private readonly SyncLinks $syncLinks,
        private readonly \App\Feed\FeedNotifier $notifier,
    ) {
    }

    /**
     * Build and publish the share, returning the fresh post.
     *
     * @param  array<int, int>  $skillIds
     */
    public function __invoke(Talent $talent, Project $project, ?string $body = null, array $skillIds = []): Post
    {
        if ($project->talent_id !== $talent->id) {
            throw new InvalidArgumentException(__('You can only share your own Projects.'));
        }

        if (! $talent->is_published) {
            throw new InvalidArgumentException(__('Publish your profile before sharing a Project.'));
        }

        $own = $talent->talentTypes()->pluck('talent_types.id')->all();

        if (array_diff($skillIds, $own) !== []) {
            throw new InvalidArgumentException(__('You can only tag your own Skills on a post.'));
        }

        $post = DB::transaction(function () use ($talent, $project, $body, $skillIds): Post {
            $post = Post::query()->create([
                'author_type' => $talent->getMorphClass(),
                'author_id' => $talent->getKey(),
                'post_type' => 'project_share',
                'body' => $body,
                'project_id' => $project->id,
                // The WIDER snapshot (5W, ADR-F2): enough to render a
                // complete-looking dead frame — never an empty box.
                'shared_snapshot' => [
                    'title' => $project->getTranslations('title'),
                    'cover_media_path' => $project->getFirstMediaUrl('cover') ?: null,
                    'client_name' => $project->client_name,
                    'year' => $project->year,
                    'media_count' => $project->loadMissing('media')->media->count(),
                    'skill_ids' => array_values(array_unique($skillIds)),
                    'shared_at' => now()->toIso8601String(),
                ],
                'state' => Draft::$name,
            ]);

            $post->skills()->attach(array_values(array_unique($skillIds)));

            if ($body !== null && $body !== '') {
                $parsed = $this->parser->parse($body);
                ($this->syncMentions)($post, $parsed);
                ($this->syncLinks)($post, $parsed);
            }

            $post->forceFill(['published_at' => now()])->save();
            $post->state->transitionTo(Published::class);

            return $post->refresh()->load(['skills', 'project', 'mentions', 'linkedUrls.preview']);
        });

        // Post-commit: mentions + the follower fan-out (feed-spec §9, Q5).
        $this->notifier->posted($talent, $post);
        $this->notifier->projectShared($talent, $post);

        return $post;
    }
}
