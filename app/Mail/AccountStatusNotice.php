<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The email an admin sends with a Verify or Suspend decision. The admin edits
 * the subject, heading and message in the moderation modal, which previews THIS
 * view (AccountNoticeService::render). Queued after commit; 'system' sender.
 */
class AccountStatusNotice extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $type  verify | suspend (picks the eyebrow + footnote)
     */
    public function __construct(
        public string $type,
        public string $subjectLine,
        public string $heading,
        public string $body,
        public ?string $ctaLabel = null,
        public ?string $ctaUrl = null,
    ) {
        $this->afterCommit();
    }

    /** Admin-written subject, sent as the 'system' profile. */
    public function envelope(): Envelope
    {
        return MailSender::envelope($this->subjectLine, 'system');
    }

    /** The account-status view. */
    public function content(): Content
    {
        return new Content(view: 'emails.account-status', with: [
            'type' => $this->type,
            'heading' => $this->heading,
            'body' => $this->body,
            'ctaLabel' => $this->ctaLabel,
            'ctaUrl' => $this->ctaUrl,
        ]);
    }
}
