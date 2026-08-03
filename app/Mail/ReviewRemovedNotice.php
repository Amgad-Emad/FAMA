<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The notice sent when an admin removes a review — explains the removal with a
 * reason, and echoes a snapshot of the removed review. Rendered from
 * resources/views/emails/review-removed.blade.php; the admin composer shows a
 * live preview built from the SAME fields.
 */
class ReviewRemovedNotice extends Mailable
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
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.review-removed',
            with: ['review' => $this->review, 'reason' => $this->reason],
        );
    }
}
