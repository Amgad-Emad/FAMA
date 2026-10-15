<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send all email
    | messages unless another mailer is explicitly specified when sending
    | the message. All additional mailers can be configured within the
    | "mailers" array. Examples of each type of mailer are provided.
    |
    */

    'default' => env('MAIL_MAILER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers that can be used
    | when delivering an email. You may specify which one you're using for
    | your mailers below. You may also add additional mailers if needed.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "resend", "log", "array",
    |            "failover", "roundrobin"
    |
    */

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            // Fail fast when the SMTP server can't be reached (e.g. a host that
            // blocks outbound 587) instead of hanging the queue worker.
            'timeout' => (int) env('MAIL_TIMEOUT', 15),
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
            'retry_after' => 60,
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
            'retry_after' => 60,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all emails sent by your application to be sent from
    | the same address. Here you may specify a name and address that is
    | used globally for all emails that are sent by your application.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', env('APP_NAME', 'Laravel')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Markdown Mail Settings
    |--------------------------------------------------------------------------
    |
    | Framework notifications (password reset, …) render through the published
    | components in resources/views/vendor/mail, which wrap the shared Fama
    | email layout (resources/views/components/email). "fama" is the theme CSS
    | in resources/views/vendor/mail/html/themes/fama.css.
    |
    */

    'markdown' => [
        'theme' => env('MAIL_MARKDOWN_THEME', 'fama'),

        'paths' => [
            resource_path('views/vendor/mail'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sender Profiles
    |--------------------------------------------------------------------------
    |
    | Named From / Reply-To pairs applied per Mailable or Notification through
    | App\Mail\MailSender (e.g. MailSender::envelope($subject, 'system')).
    | Outgoing mail leaves from any address on the verified sending domain;
    | human replies land in the real mailboxes via Reply-To. There is
    | deliberately no global "reply_to" here: Laravel ADDS a global Reply-To
    | to a message's own instead of replacing it, so it would double up.
    | Every address comes from the environment — switching provider or
    | mailbox is an .env change only.
    |
    */

    'senders' => [

        // Automated system mail: no-reply From, replies routed to support.
        'system' => [
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'name' => env('MAIL_FROM_NAME', env('APP_NAME', 'Laravel')),
            ],
            'reply_to' => [
                'address' => env('MAIL_SUPPORT_ADDRESS'),
                'name' => env('MAIL_SUPPORT_NAME', env('APP_NAME', 'Laravel')),
            ],
        ],

        'support' => [
            'from' => [
                'address' => env('MAIL_SUPPORT_ADDRESS'),
                'name' => env('MAIL_SUPPORT_NAME', env('APP_NAME', 'Laravel')),
            ],
            'reply_to' => [
                'address' => env('MAIL_SUPPORT_ADDRESS'),
                'name' => env('MAIL_SUPPORT_NAME', env('APP_NAME', 'Laravel')),
            ],
        ],

        'info' => [
            'from' => [
                'address' => env('MAIL_INFO_ADDRESS'),
                'name' => env('MAIL_INFO_NAME', env('APP_NAME', 'Laravel')),
            ],
            'reply_to' => [
                'address' => env('MAIL_INFO_ADDRESS'),
                'name' => env('MAIL_INFO_NAME', env('APP_NAME', 'Laravel')),
            ],
        ],

        'contact' => [
            'from' => [
                'address' => env('MAIL_CONTACT_ADDRESS'),
                'name' => env('MAIL_CONTACT_NAME', env('APP_NAME', 'Laravel')),
            ],
            'reply_to' => [
                'address' => env('MAIL_CONTACT_ADDRESS'),
                'name' => env('MAIL_CONTACT_NAME', env('APP_NAME', 'Laravel')),
            ],
        ],

    ],

];
