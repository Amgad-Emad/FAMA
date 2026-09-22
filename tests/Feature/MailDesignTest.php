<?php

use App\Mail\ReviewRemovedNotice;
use App\Mail\TestMail;
use App\Models\Talent;
use Illuminate\Auth\Notifications\ResetPassword;

/**
 * Every email renders inside the shared Fama shell (components/email/layout):
 * PNG logo, brand band, footer — including the framework's markdown
 * notifications, which route through vendor/mail/html/layout.
 */
function assertFamaShell(string $html): void
{
    expect($html)
        ->toContain('images/email/fama-logo.png')
        ->toContain('#FFE72F')          // the brand band's yellow star segment
        ->toContain('#FAF6E0')          // cream ground
        ->toContain('fm-card')
        ->not->toContain('laravel.com/img/notification-logo');
}

it('renders the review-removed notice on the Fama shell', function () {
    $html = (new ReviewRemovedNotice(
        ['reviewer_name' => 'Jo', 'talent' => 'Layla', 'rating' => 4, 'body' => 'Late <b>start</b>'],
        "Line one\nLine two",
        'A review was removed',
    ))->render();

    assertFamaShell($html);
    expect($html)
        ->toContain('Moderation notice')
        ->toContain('Line one')
        ->toContain('Late &lt;b&gt;start&lt;/b&gt;') // reviewer text stays escaped
        ->toContain('aria-label="4/5"');
});

it('renders the mail:test email on the Fama shell with its details', function () {
    $html = (new TestMail('system'))->render();

    assertFamaShell($html);
    expect($html)->toContain('Your mail is working')->toContain('system');
});

it('renders framework markdown notifications (password reset) on the Fama shell with the theme inlined', function () {
    $talent = Talent::factory()->make(['email' => 'reset@example.com']);

    $html = (string) (new ResetPassword('token-123'))->toMail($talent)->render();

    assertFamaShell($html);
    expect($html)
        ->toContain('token-123')
        ->toContain('fm-btn')                               // the shared pill button
        ->toMatch('/<h1 style="[^"]*Bricolage Grotesque/'); // fama.css inlined into the markdown
});

it('renders right-to-left in Arabic', function () {
    app()->setLocale('ar');

    $html = (new TestMail('system'))->render();

    expect($html)->toContain('dir="rtl"')->toContain('lang="ar"');
});
