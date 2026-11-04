<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent once, the first time a talent's Go Live steps are done and the profile
 * goes Live (ADR-AC — no review step): "you're Live, your link works; next,
 * something to show". Triggered by ProfileCompletionService::notifyIfComplete().
 * Queued after commit, sent as the 'system' sender profile.
 */
class ProfileStepsCompleted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public string $name, public string $profileUrl)
    {
        $this->afterCommit();
    }

    /** Subject + the 'system' sender profile (config/mail.php). */
    public function envelope(): Envelope
    {
        return MailSender::envelope(__('You’re Live on Fama'), 'system');
    }

    /** The branded view. */
    public function content(): Content
    {
        return new Content(
            view: 'emails.profile-steps-completed',
            with: ['name' => $this->name, 'profileUrl' => $this->profileUrl],
        );
    }
}
