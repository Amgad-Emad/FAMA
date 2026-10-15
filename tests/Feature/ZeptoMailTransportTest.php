<?php

use App\Mail\TestMail;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

/*
 * The zeptomail mailer sends over ZeptoMail's HTTPS API (for hosts that block
 * outbound SMTP). These tests fake the HTTP layer — no real mail leaves.
 */

beforeEach(function () {
    config([
        'mail.default' => 'zeptomail',
        'mail.mailers.zeptomail.token' => 'Zoho-enczapikey secret-token',
        'mail.mailers.zeptomail.host' => 'api.zeptomail.com',
    ]);
});

it('posts the message to the ZeptoMail API with the token', function () {
    Http::fake(['api.zeptomail.com/*' => Http::response(['request_id' => 'abc123', 'data' => []], 201)]);

    Mail::mailer('zeptomail')->to('someone@example.com', 'Some One')->send(new TestMail('system'));

    Http::assertSent(function (Request $request) {
        $body = $request->data();

        return $request->url() === 'https://api.zeptomail.com/v1.1/email'
            && $request->hasHeader('Authorization', 'Zoho-enczapikey secret-token') // prefix not doubled
            && $body['to'][0]['email_address']['address'] === 'someone@example.com'
            && $body['to'][0]['email_address']['name'] === 'Some One'
            && filled($body['from']['address'])
            && filled($body['subject'])
            && filled($body['htmlbody'] ?? null);
    });
});

it('turns an API error into a transport failure', function () {
    Http::fake(['api.zeptomail.com/*' => Http::response(['error' => ['code' => 'TM_3201', 'message' => 'Invalid API Token found']], 401)]);

    Mail::mailer('zeptomail')->to('someone@example.com')->send(new TestMail('system'));
})->throws(Symfony\Component\Mailer\Exception\TransportException::class, 'Invalid API Token found');
