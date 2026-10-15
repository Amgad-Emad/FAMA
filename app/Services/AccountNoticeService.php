<?php

namespace App\Services;

use App\Mail\AccountStatusNotice;
use App\Models\Brand;
use App\Models\Talent;
use Illuminate\Support\Facades\Mail;

/**
 * The email that goes with an admin's Verify / Suspend decision: suggested
 * copy (defaults), the live preview the moderation modal shows (render), and
 * the send. One home, so the preview and the sent email are the same view.
 */
class AccountNoticeService extends Service
{
    /** The decisions that offer an email. */
    public const TYPES = ['verify', 'suspend'];

    /**
     * Suggested email for this account + decision; the admin edits it.
     *
     * @return array{email: string, subject: string, heading: string, message: string, cta_label: ?string, cta_url: ?string}
     */
    public function defaults(Talent|Brand $account, string $type): array
    {
        $name = (string) ($account instanceof Talent
            ? ($account->display_name ?: $account->slug)
            : ($account->name ?? ''));

        if ($type === 'verify') {
            return [
                'email' => (string) $account->email,
                'subject' => __('Your Fama profile has been checked'),
                'heading' => __('You’re checked, :name', ['name' => $name]),
                'message' => __("Good news — our team has checked your profile, and it's approved.\n\nYou can go Live whenever you're ready: open Profile structure and press Go Live. Your link starts working for everyone, and you can be found in the Creatives Index."),
                'cta_label' => $account instanceof Talent ? __('Go Live') : null,
                'cta_url' => $account instanceof Talent ? route('talent.profile.edit') : null,
            ];
        }

        return [
            'email' => (string) $account->email,
            'subject' => __('Your Fama account has been suspended'),
            'heading' => __('Your account is suspended'),
            'message' => __("Hi :name,\n\nOur team has suspended your Fama account, so your profile is hidden from everyone for now.\n\nIf you think this is a mistake, reply to this email and we'll take another look.", ['name' => $name]),
            'cta_label' => null,
            'cta_url' => null,
        ];
    }

    /**
     * The email as HTML, from the given copy (preview = the real view).
     *
     * @param  array{subject: string, heading: string, message: string, cta_label?: ?string, cta_url?: ?string}  $copy
     */
    public function render(string $type, array $copy): string
    {
        return $this->mailable($type, $copy)->render();
    }

    /**
     * Queue the email (after the surrounding transaction commits).
     *
     * @param  array{email: string, subject: string, heading: string, message: string, cta_label?: ?string, cta_url?: ?string}  $copy
     */
    public function send(string $type, array $copy): void
    {
        Mail::to($copy['email'])->queue($this->mailable($type, $copy));
    }

    /** Build the mailable from edited copy. */
    private function mailable(string $type, array $copy): AccountStatusNotice
    {
        return new AccountStatusNotice(
            $type,
            (string) $copy['subject'],
            (string) $copy['heading'],
            (string) $copy['message'],
            $copy['cta_label'] ?? null,
            $copy['cta_url'] ?? null,
        );
    }
}
