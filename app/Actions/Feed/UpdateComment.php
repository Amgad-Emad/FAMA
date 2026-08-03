<?php

namespace App\Actions\Feed;

use App\Models\Comment;
use App\Services\RichTextParser;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Edit a comment inside the 15-minute window (D11): re-parse, re-sync
 * mentions/links (full replace — offsets shift), stamp edited_at.
 */
class UpdateComment
{
    public function __construct(
        private readonly RichTextParser $parser,
        private readonly SyncMentions $syncMentions,
        private readonly SyncLinks $syncLinks,
    ) {
    }

    /**
     * Apply the edit and return the fresh comment.
     */
    public function __invoke(Comment $comment, string $body): Comment
    {
        $body = trim($body);

        if ($body === '') {
            throw new InvalidArgumentException(__('A comment needs a body.'));
        }

        if (! $comment->isEditable()) {
            throw new InvalidArgumentException(
                __('Comments lock :minutes minutes after posting.', ['minutes' => config('feed.edit_window_minutes')])
            );
        }

        return DB::transaction(function () use ($comment, $body): Comment {
            $comment->forceFill(['body' => $body, 'edited_at' => now()])->save();

            $parsed = $this->parser->parse($body);
            ($this->syncMentions)($comment, $parsed);
            ($this->syncLinks)($comment, $parsed);

            return $comment->refresh();
        });
    }
}
