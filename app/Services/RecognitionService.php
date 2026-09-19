<?php

namespace App\Services;

use App\Models\Recognition;
use App\Models\Talent;
use App\States\Recognition\Approved;
use App\States\Recognition\Rejected;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Recognitions — peer vouches, talent → talent (ADR-Z).
 *
 * The ONLY write path. Reviews and recognitions are siblings that differ in who
 * writes them (brand → talent after a booking vs talent → talent, invited), so
 * this service deliberately mirrors the review moderation surface rather than
 * inventing a parallel vocabulary: submit lands pending, and the admin queue
 * approves or rejects.
 */
class RecognitionService extends Service
{
    /**
     * One peer vouches for another.
     *
     * Two rules live here and nowhere else:
     *  - You cannot vouch for yourself. A self-recognition is not a vouch.
     *  - A peer vouches ONCE. Writing again REPLACES the earlier vouch and sends
     *    it back through moderation, rather than throwing — the alternative is a
     *    dead end for someone who simply wants to reword what they said. The
     *    unique index on (talent_id, author_talent_id) is what this respects.
     *
     * Always lands PENDING: an approved vouch that is then edited must not stay
     * public on the strength of the old moderation decision.
     *
     * @param  array<string, mixed>  $data
     */
    public function submit(Talent $subject, Talent $author, array $data): Recognition
    {
        if ($subject->is($author)) {
            throw new InvalidArgumentException(__('You cannot write a recognition for yourself.'));
        }

        return $this->runInTransaction(function () use ($subject, $author, $data): Recognition {
            $recognition = Recognition::withTrashed()->firstOrNew([
                'talent_id' => $subject->getKey(),
                'author_talent_id' => $author->getKey(),
            ]);

            $recognition->fill([
                'relationship' => $data['relationship'],
                'body' => $data['body'],
                'recognized_at' => now(),
            ]);

            // A re-submission is a fresh claim: restore it if it was withdrawn,
            // and reset moderation regardless of where it had got to.
            $recognition->deleted_at = null;
            $recognition->status = 'pending';
            $recognition->is_approved = false;
            $recognition->save();

            Log::channel('app')->info('Recognition submitted.', [
                'recognition_id' => $recognition->id,
                'talent_id' => $subject->id,
                'author_talent_id' => $author->id,
            ]);

            return $recognition;
        }, ['talent_id' => $subject->id, 'author_talent_id' => $author->id]);
    }

    /**
     * Clear a vouch for public display. `is_approved` follows the state through
     * SyncStateProjections — never set it directly.
     */
    public function approve(Recognition $recognition): Recognition
    {
        return $this->runInTransaction(function () use ($recognition): Recognition {
            $recognition->status->transitionTo(Approved::class);

            return $recognition->refresh();
        }, ['recognition_id' => $recognition->id]);
    }

    /**
     * Reject a pending vouch, or hide one already public.
     */
    public function reject(Recognition $recognition): Recognition
    {
        return $this->runInTransaction(function () use ($recognition): Recognition {
            $recognition->status->transitionTo(Rejected::class);

            return $recognition->refresh();
        }, ['recognition_id' => $recognition->id]);
    }

    /**
     * Withdraw a vouch you wrote. Soft-deleted so re-submitting later restores
     * the same row and the unique index still holds.
     */
    public function withdraw(Recognition $recognition): void
    {
        $this->runInTransaction(function () use ($recognition): void {
            $recognition->delete();
        }, ['recognition_id' => $recognition->id]);
    }
}
