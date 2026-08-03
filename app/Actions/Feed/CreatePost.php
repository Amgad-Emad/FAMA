<?php

namespace App\Actions\Feed;

use App\Data\Feed\CreatePostData;
use App\Feed\Actor;
use App\Models\Post;
use App\Models\Talent;
use App\Services\RichTextParser;
use App\States\Post\Draft;
use App\States\Post\Published;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Create and publish a note or media post (feed-spec §4). One transaction:
 * persist (draft) → attach skills (own skills only) → attach media (ordered
 * post_media collection, queued conversions) → parse + sync mentions/links →
 * stamp published_at → transition to Published (PostObserver counts it,
 * ADR-F6). Notifications attach in 5J.
 */
class CreatePost
{
    public function __construct(
        private readonly RichTextParser $parser,
        private readonly SyncMentions $syncMentions,
        private readonly SyncLinks $syncLinks,
        private readonly \App\Feed\FeedNotifier $notifier,
    ) {
    }

    /**
     * Persist and publish the post, returning it fresh.
     */
    public function __invoke(Actor $author, CreatePostData $data): Post
    {
        $skillIds = $this->validateSkills($author, $data->skillIds);

        $post = DB::transaction(function () use ($author, $data, $skillIds): Post {
            $post = Post::query()->create([
                'author_type' => $author->getMorphClass(),
                'author_id' => $author->getKey(),
                'post_type' => $data->postType,
                'body' => $data->body,
                'state' => Draft::$name,
            ]);

            $post->skills()->attach($skillIds);

            foreach ($data->media as $index => $file) {
                $post->addMedia($file)
                    ->withCustomProperties(array_filter(['alt' => $data->mediaAlt[$index] ?? null]))
                    ->toMediaCollection('post_media');
            }

            if ($data->body !== null && $data->body !== '') {
                $parsed = $this->parser->parse($data->body);
                ($this->syncMentions)($post, $parsed);
                ($this->syncLinks)($post, $parsed);
            }

            $post->forceFill(['published_at' => now()])->save();
            $post->state->transitionTo(Published::class);

            return $post->refresh()->load(['skills', 'mentions', 'linkedUrls.preview', 'media']);
        });

        // Post-commit mention notifications (feed-spec §9).
        $this->notifier->posted($author, $post);

        return $post;
    }

    /**
     * Skills must belong to the authoring talent's own skills (feed-spec §4);
     * anything else is rejected with a clear message. Brands (future authors)
     * have no skills — any tag is rejected.
     *
     * @param  array<int, int>  $skillIds
     * @return array<int, int>
     */
    private function validateSkills(Actor $author, array $skillIds): array
    {
        if ($skillIds === []) {
            return [];
        }

        $own = $author instanceof Talent
            ? $author->talentTypes()->pluck('talent_types.id')->all()
            : [];

        $foreign = array_diff($skillIds, $own);

        if ($foreign !== []) {
            throw ValidationException::withMessages([
                'skill_ids' => __('You can only tag your own Skills on a post.'),
            ]);
        }

        return array_values(array_unique($skillIds));
    }
}
