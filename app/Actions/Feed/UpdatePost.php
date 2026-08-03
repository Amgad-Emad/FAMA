<?php

namespace App\Actions\Feed;

use App\Models\Post;
use App\Models\Talent;
use App\Services\RichTextParser;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Illuminate\Validation\ValidationException;

/**
 * Edit a post inside the 15-minute window (D11): re-parse the body, re-sync
 * mentions and links (offsets shift — full replace, ADR-F3), re-tag skills,
 * stamp edited_at. The MEDIA SET IS IMMUTABLE after publish — this action
 * deliberately accepts no files.
 */
class UpdatePost
{
    public function __construct(
        private readonly RichTextParser $parser,
        private readonly SyncMentions $syncMentions,
        private readonly SyncLinks $syncLinks,
    ) {
    }

    /**
     * Apply the edit and return the fresh post.
     *
     * @param  array<int, int>|null  $skillIds  null = leave tags untouched
     */
    public function __invoke(Post $post, ?string $body, ?array $skillIds = null): Post
    {
        if (! $post->isEditable()) {
            throw new InvalidArgumentException(
                __('Posts lock :minutes minutes after publishing.', ['minutes' => config('feed.edit_window_minutes')])
            );
        }

        if (($body === null || trim($body) === '')
            && $post->post_type === 'note') {
            throw ValidationException::withMessages(['body' => __('A note needs a body.')]);
        }

        return DB::transaction(function () use ($post, $body, $skillIds): Post {
            $post->forceFill(['body' => $body, 'edited_at' => now()])->save();

            $parsed = $this->parser->parse((string) $body);
            ($this->syncMentions)($post, $parsed);
            ($this->syncLinks)($post, $parsed);

            if ($skillIds !== null) {
                $author = $post->author()->withoutGlobalScopes()->first();

                if ($author instanceof Talent) {
                    $own = $author->talentTypes()->pluck('talent_types.id')->all();

                    if (array_diff($skillIds, $own) !== []) {
                        throw ValidationException::withMessages([
                            'skill_ids' => __('You can only tag your own Skills on a post.'),
                        ]);
                    }
                }

                $post->skills()->sync(array_values(array_unique($skillIds)));
            }

            return $post->refresh()->load(['skills', 'mentions', 'linkedUrls.preview']);
        });
    }
}
