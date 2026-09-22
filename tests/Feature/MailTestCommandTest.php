<?php

use App\Mail\MailSender;
use App\Mail\ReviewRemovedNotice;
use App\Mail\TestMail;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    config([
        'mail.senders.system.from' => ['address' => 'noreply@fama.test', 'name' => 'Fama'],
        'mail.senders.system.reply_to' => ['address' => 'support@fama.test', 'name' => 'Fama Support'],
    ]);
});

it('sends a test email synchronously as the system sender', function () {
    Mail::fake();

    $this->artisan('mail:test', ['to' => 'someone@example.com'])
        ->expectsOutputToContain('Test email sent to someone@example.com.')
        ->assertSuccessful();

    Mail::assertSent(TestMail::class, fn (TestMail $mail) => $mail->hasTo('someone@example.com')
        && $mail->hasFrom('noreply@fama.test')
        && $mail->hasReplyTo('support@fama.test'));
});

it('queues the test email with --queue', function () {
    Mail::fake();

    $this->artisan('mail:test', ['to' => 'someone@example.com', '--queue' => true])
        ->expectsOutputToContain('Queued a test email')
        ->assertSuccessful();

    Mail::assertQueued(TestMail::class);
    Mail::assertNotSent(TestMail::class);
});

it('rejects an invalid address and an unknown sender profile', function () {
    Mail::fake();

    $this->artisan('mail:test', ['to' => 'not-an-email'])->assertFailed();
    $this->artisan('mail:test', ['to' => 'a@example.com', '--sender' => 'nope'])->assertFailed();

    Mail::assertNothingOutgoing();
});

it('prints the exception message when the transport fails', function () {
    Mail::shouldReceive('to->send')->andThrow(new RuntimeException('Connection could not be established'));

    $this->artisan('mail:test', ['to' => 'someone@example.com'])
        ->expectsOutputToContain('Connection could not be established')
        ->assertFailed();
});

it('resolves sender profiles from config and falls back to the global from', function () {
    config([
        'mail.from' => ['address' => 'global@fama.test', 'name' => 'Global'],
        'mail.senders.info' => ['from' => ['address' => null, 'name' => null], 'reply_to' => ['address' => null]],
    ]);

    expect(MailSender::from('info')->address)->toBe('global@fama.test')
        ->and(MailSender::replyTo('info'))->toBe([])
        ->and(MailSender::replyTo('system')[0]->address)->toBe('support@fama.test');

    expect(fn () => MailSender::from('typo'))->toThrow(InvalidArgumentException::class);
});

it('queues the review-removed notice as the system sender', function () {
    $mail = new ReviewRemovedNotice(['reviewer_name' => 'Jo'], 'Reason', 'Subject');

    expect($mail)->toBeInstanceOf(Illuminate\Contracts\Queue\ShouldQueue::class)
        ->and($mail->afterCommit)->toBeTrue();

    $mail->assertFrom('noreply@fama.test');
    $mail->assertHasReplyTo('support@fama.test');
});
