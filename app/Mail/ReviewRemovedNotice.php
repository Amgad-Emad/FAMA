<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The notice sent when an admin removes a review — explains the removal with a
 * reason, and echoes a snapshot of the removed review. Rendered from
 * resources/views/emails/review-removed.blade.php; the admin composer shows a
 * live preview built from the SAME fields.
 *
 * Queued (ShouldQueue, after commit) so the admin's request never waits on
 * SMTP; sent as the 'system' sender profile (no-reply From, Reply-To support).
 */
class ReviewRemovedNotice extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array{reviewer_name?: string, talent?: string, rating?: int, body?: string}  $review
     */
    public function __construct(
        public array $review,
        public string $reason,
        public string $subjectLine,
    ) {
        $this->afterCommit();
    }

    /**
     * Admin-written subject, sent as the 'system' profile (config/mail.php).
     */
    public function envelope(): Envelope
    {
        return MailSender::envelope($this->subjectLine, 'system');
    }

    /**
     * The review-removed Blade view with the snapshot + reason.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.review-removed',
            with: ['review' => $this->review, 'reason' => $this->reason],
        );
    }
}
