<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent once, the first time a talent finishes the six profile steps while Fama
 * has not checked them yet: "you're done — our team checks it next".
 * Triggered by ProfileCompletionService::notifyIfComplete(). Queued after
 * commit, sent as the 'system' sender profile.
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
        return MailSender::envelope(__('Your profile is complete — we’re checking it now'), 'system');
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
