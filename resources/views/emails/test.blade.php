{{-- Sent by `php artisan mail:test` (App\Mail\TestMail) — a delivery check that doubles as a preview of the shared email layout. --}}
@php
    use App\Mail\EmailTheme as T;
@endphp
<x-email.layout
    :title="__('Your mail is working')"
    :eyebrow="__('Delivery check')"
    :preheader="__('If you can read this, outgoing mail from :app works.', ['app' => config('app.name')])"
    :footnote="__('Sent from the mail:test command. No action is needed.')">

    <p class="fm-muted" style="margin:0;color:{{ T::color('muted') }};">
        {{ __('This is a test email from :app. If it reached your inbox, the mail transport, sender and queue settings are all good.', ['app' => config('app.name')]) }}
    </p>

    <x-email.callout tone="success" :label="__('Delivered')">
        {{ __('Outgoing mail works.') }}
    </x-email.callout>

    <x-email.panel :label="__('Details')">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="font-family:{{ T::font('body') }};font-size:13px;line-height:1.6;">
            @foreach ([__('Mailer') => $mailer, __('Sender profile') => $sender, __('Sent at') => $sentAt] as $label => $value)
                <tr>
                    <td class="fm-subtle" style="padding:4px 0;width:40%;color:{{ T::color('subtle') }};">{{ $label }}</td>
                    <td class="fm-ink" style="padding:4px 0;font-family:{{ T::font('mono') }};color:{{ T::color('ink') }};">{{ $value }}</td>
                </tr>
            @endforeach
        </table>
    </x-email.panel>

    <x-email.button :href="config('app.url')">{{ __('Open :app', ['app' => config('app.name')]) }}</x-email.button>
</x-email.layout>
