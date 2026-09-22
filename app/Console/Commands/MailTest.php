<?php

namespace App\Console\Commands;

use App\Mail\MailSender;
use App\Mail\TestMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Sends a test email so the mail transport (ZeptoMail SMTP or whatever .env
 * points at) can be verified from the terminal. Sends synchronously by default
 * so a transport failure prints its exception here; `--queue` exercises the
 * queue + worker path instead. Never prints credentials.
 */
class MailTest extends Command
{
    protected $signature = 'mail:test
                            {to : Recipient email address}
                            {--sender=system : Sender profile from config/mail.php "senders"}
                            {--queue : Queue the email instead of sending it now}';

    protected $description = 'Send a test email through the configured mailer';

    /**
     * Validate input, show the (non-secret) transport settings, send, and
     * report success or the exception message.
     */
    public function handle(): int
    {
        $to = (string) $this->argument('to');
        $sender = (string) $this->option('sender');

        if (Validator::make(['to' => $to], ['to' => 'required|email'])->fails()) {
            $this->error("\"{$to}\" is not a valid email address.");

            return self::FAILURE;
        }

        if (! is_array(config("mail.senders.{$sender}"))) {
            $this->error("Unknown sender profile \"{$sender}\". Available: ".implode(', ', array_keys(config('mail.senders', []))));

            return self::FAILURE;
        }

        $this->printSettings($sender);

        try {
            if ($this->option('queue')) {
                Mail::to($to)->queue(new TestMail($sender));
                $this->info("Queued a test email to {$to} on connection \"".config('queue.default').'".');
                $this->line('Run `php artisan queue:work` to deliver it; failures land in `php artisan queue:failed`.');

                return self::SUCCESS;
            }

            Mail::to($to)->send(new TestMail($sender));
        } catch (Throwable $e) {
            $this->error('Sending failed: '.$e::class);
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Test email sent to {$to}.");

        return self::SUCCESS;
    }

    /**
     * Print the resolved transport settings — never the username or password.
     */
    private function printSettings(string $sender): void
    {
        $mailer = (string) config('mail.default');
        $config = (array) config("mail.mailers.{$mailer}", []);
        $from = MailSender::from($sender);
        $replyTo = MailSender::replyTo($sender)[0] ?? null;

        $this->table(['Setting', 'Value'], [
            ['Mailer', $mailer],
            ['Transport', $config['transport'] ?? '—'],
            ['Host', $config['host'] ?? '—'],
            ['Port', $config['port'] ?? '—'],
            ['Scheme', $config['scheme'] ?? '(auto)'],
            ['From', "{$from->name} <{$from->address}>"],
            ['Reply-To', $replyTo ? "{$replyTo->name} <{$replyTo->address}>" : '—'],
        ]);
    }
}
