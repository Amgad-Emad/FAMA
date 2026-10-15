<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

/**
 * Sends mail through ZeptoMail's HTTPS API (POST /v1.1/email) instead of SMTP.
 *
 * Why: shared hosts often block outbound SMTP ports (587 / 465) while HTTPS
 * (443) is always open. The token is the same ZeptoMail "Send Mail token" the
 * SMTP setup uses as its password — the API wants it as
 * `Authorization: Zoho-enczapikey <token>`.
 *
 * Enable with MAIL_MAILER=zeptomail (config/mail.php 'zeptomail' mailer).
 */
class ZeptoMailTransport extends AbstractTransport
{
    public function __construct(
        private readonly string $token,
        private readonly string $host = 'api.zeptomail.com',
        private readonly int $timeout = 15,
    ) {
        parent::__construct();
    }

    /** Build the API payload from the message and send it; a non-2xx answer throws. */
    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();

        $from = $envelope->getSender();
        $to = $this->addresses($email->getTo());
        $cc = $this->addresses($email->getCc());
        $bcc = $this->addresses($email->getBcc());

        // Envelope recipients not named in To/Cc/Bcc (e.g. Mail::alwaysTo) still get it.
        $named = array_map(fn ($r) => strtolower($r['email_address']['address']), [...$to, ...$cc, ...$bcc]);
        foreach ($envelope->getRecipients() as $recipient) {
            if (! in_array(strtolower($recipient->getAddress()), $named, true)) {
                $bcc[] = $this->address($recipient);
            }
        }

        $payload = array_filter([
            'from' => ['address' => $from->getAddress(), 'name' => $this->fromName($email, $from)],
            'to' => $to,
            'cc' => $cc,
            'bcc' => $bcc,
            'reply_to' => array_map(fn (Address $a) => array_filter(['address' => $a->getAddress(), 'name' => $a->getName()]), $email->getReplyTo()),
            'subject' => (string) $email->getSubject(),
            'htmlbody' => $email->getHtmlBody() !== null ? (string) $email->getHtmlBody() : null,
            'textbody' => $email->getTextBody() !== null ? (string) $email->getTextBody() : null,
            'attachments' => $this->attachments($email),
        ], fn ($v) => $v !== null && $v !== []);

        $response = Http::withHeaders([
            'Authorization' => 'Zoho-enczapikey '.$this->cleanToken(),
            'Accept' => 'application/json',
        ])->timeout($this->timeout)->asJson()->post("https://{$this->host}/v1.1/email", $payload);

        if (! $response->successful()) {
            $detail = $response->json('error.details.0.message') ?? $response->json('error.message') ?? $response->body();

            throw new TransportException(sprintf('ZeptoMail API error (HTTP %d): %s', $response->status(), is_string($detail) ? $detail : json_encode($detail)));
        }

        if ($id = $response->json('request_id')) {
            $message->setMessageId((string) $id);
        }
    }

    /** "zeptomail+api://api.zeptomail.com" — shown in logs / debugging. */
    public function __toString(): string
    {
        return 'zeptomail+api://'.$this->host;
    }

    /**
     * @param  list<Address>  $addresses
     * @return list<array{email_address: array{address: string, name?: string}}>
     */
    private function addresses(array $addresses): array
    {
        return array_map(fn (Address $a) => $this->address($a), $addresses);
    }

    /** One recipient in ZeptoMail's shape. */
    private function address(Address $a): array
    {
        return ['email_address' => array_filter(['address' => $a->getAddress(), 'name' => $a->getName()])];
    }

    /** The From display name (falls back to the address's own name). */
    private function fromName(Email $email, Address $from): ?string
    {
        foreach ($email->getFrom() as $address) {
            if (strtolower($address->getAddress()) === strtolower($from->getAddress()) && $address->getName() !== '') {
                return $address->getName();
            }
        }

        return $from->getName() ?: null;
    }

    /**
     * File attachments, base64-encoded.
     *
     * @return list<array{content: string, mime_type: string, name: string}>
     */
    private function attachments(Email $email): array
    {
        $out = [];
        foreach ($email->getAttachments() as $part) {
            $out[] = [
                'content' => base64_encode($part->getBody()),
                'mime_type' => $part->getMediaType().'/'.$part->getMediaSubtype(),
                'name' => $part->getFilename() ?? 'attachment',
            ];
        }

        return $out;
    }

    /** Accept the token with or without the "Zoho-enczapikey " prefix copied from the dashboard. */
    private function cleanToken(): string
    {
        return trim(preg_replace('/^Zoho-enczapikey\s+/i', '', trim($this->token)) ?? '');
    }
}
