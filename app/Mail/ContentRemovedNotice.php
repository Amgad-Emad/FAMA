<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The email an author gets when staff remove their post or comment: the
 * admin's message, the reason (highlighted) and a quote of what was removed.
 * The Posts module's remove dialog previews THIS view
 * (FeedModerationController::removalNotice). Queued after commit; 'system' sender.
 */
class ContentRemovedNotice extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  'post'|'comment'  $kind
     */
    public function __construct(
        public string $kind,
        public string $subjectLine,
        public string $body,
        public string $reason,
        public string $excerpt,
    ) {
        $this->afterCommit();
    }

    /** Admin-written subject, 'system' sender profile. */
    public function envelope(): Envelope
    {
        return MailSender::envelope($this->subjectLine, 'system');
    }

    /** The content-removed view. */
    public function content(): Content
    {
        return new Content(view: 'emails.content-removed', with: [
            'kind' => $this->kind,
            'body' => $this->body,
            'reason' => $this->reason,
            'excerpt' => $this->excerpt,
        ]);
    }
}
