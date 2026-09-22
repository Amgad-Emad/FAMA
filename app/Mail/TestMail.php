<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * A minimal delivery check sent by `php artisan mail:test`. Deliberately NOT
 * ShouldQueue: the command sends it synchronously by default so SMTP errors
 * surface in the terminal; `--queue` pushes it through the worker instead.
 */
class TestMail extends Mailable
{
    use Queueable;

    /**
     * @param  string  $sender  The config('mail.senders') profile to send as.
     */
    public function __construct(public string $sender = 'system')
    {
    }

    /**
     * Subject plus the sender profile's From / Reply-To.
     */
    public function envelope(): Envelope
    {
        return MailSender::envelope(config('app.name').' — mail test', $this->sender);
    }

    /**
     * The branded test view (emails/test) on the shared Fama email layout,
     * with the non-secret details it reports.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.test',
            with: [
                'mailer' => (string) config('mail.default'),
                'sender' => $this->sender,
                'sentAt' => now()->toDayDateTimeString(),
            ],
        );
    }
}
