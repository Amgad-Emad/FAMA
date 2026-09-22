<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Notifications\Messages\MailMessage;
use InvalidArgumentException;

/**
 * Resolves a named sender profile from config('mail.senders') into From /
 * Reply-To addresses, so every Mailable and mail Notification states WHO it
 * speaks as ('system', 'support', 'info', 'contact') instead of hardcoding
 * addresses or leaning on the single global MAIL_FROM_ADDRESS. All addresses
 * come from .env, so changing a mailbox or provider never touches code.
 */
final class MailSender
{
    /**
     * Build a Mailable Envelope carrying the profile's From + Reply-To.
     */
    public static function envelope(string $subject, string $sender = 'system'): Envelope
    {
        return new Envelope(
            from: self::from($sender),
            replyTo: self::replyTo($sender),
            subject: $subject,
        );
    }

    /**
     * Apply the profile's From + Reply-To to a Notification's MailMessage
     * (for toMail()); returns the same message for chaining.
     */
    public static function apply(MailMessage $message, string $sender = 'system'): MailMessage
    {
        $from = self::from($sender);
        $message->from($from->address, $from->name);

        foreach (self::replyTo($sender) as $replyTo) {
            $message->replyTo($replyTo->address, $replyTo->name);
        }

        return $message;
    }

    /**
     * The profile's From address. Falls back to the global mail.from when the
     * profile's address is not configured, so an unset .env line degrades to
     * the default sender instead of producing an address-less message.
     */
    public static function from(string $sender = 'system'): Address
    {
        $profile = self::profile($sender);

        return new Address(
            $profile['from']['address'] ?? config('mail.from.address'),
            $profile['from']['name'] ?? config('mail.from.name'),
        );
    }

    /**
     * The profile's Reply-To addresses — empty when none is configured (the
     * reply then goes to From, which is the mail client's default).
     *
     * @return list<Address>
     */
    public static function replyTo(string $sender = 'system'): array
    {
        $replyTo = self::profile($sender)['reply_to'] ?? [];

        if (blank($replyTo['address'] ?? null)) {
            return [];
        }

        return [new Address($replyTo['address'], $replyTo['name'] ?? null)];
    }

    /**
     * Look up a profile, failing loudly on a typo'd name rather than silently
     * sending as the wrong identity.
     *
     * @return array{from?: array{address?: ?string, name?: ?string}, reply_to?: array{address?: ?string, name?: ?string}}
     */
    private static function profile(string $sender): array
    {
        $profile = config("mail.senders.{$sender}");

        if (! is_array($profile)) {
            throw new InvalidArgumentException("Unknown mail sender profile [{$sender}].");
        }

        return $profile;
    }
}
